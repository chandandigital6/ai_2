<?php
namespace App\Http\Controllers;

use App\Models\UserAddress;
use App\Models\State;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class GrowyaCustomerAddressController extends Controller
{
    public function index(Request $request)
    {
        $addresses = UserAddress::query()->where('user_id',$request->user()->id)
            ->with('state:id,name','city:id,name')->orderByDesc('is_default')->latest('id')->get();
        $states = State::query()->where('is_active',true)->orderBy('name')->get(['id','name']);
        return view('customer-addresses.index',compact('addresses','states'));
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'label'=>['required',Rule::in(['Home','Office','Other'])],
            'recipient_name'=>['required','string','max:255'],
            'phone'=>['required','regex:/^[6-9][0-9]{9}$/'],
            'address'=>['required','string','max:1000'],
            'state_id'=>['required','integer',Rule::exists('states','id')->where('is_active',true)],
            'city_name'=>['required','string','max:100'],
            'pincode'=>['required','regex:/^[1-9][0-9]{5}$/'],
            'is_default'=>['nullable','boolean'],
        ]);
    }

    private function prepared(array $data): array
    {
        $cityName=trim($data['city_name']);
        $city=City::query()->where('state_id',$data['state_id'])
            ->where('is_active',true)->where('name',$cityName)->first();
        // city_id is nullable in the existing schema; custom city names remain editable
        // for checkout, but the address book should contain recognized city names.
        if (!$city) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'city_name'=>'Please enter a city listed for the selected state.',
            ]);
        }
        return [
            'label'=>$data['label'], 'recipient_name'=>$data['recipient_name'],
            'phone'=>$data['phone'], 'address'=>$data['address'],
            'state_id'=>(int)$data['state_id'], 'city_id'=>$city->id,
            'pincode'=>$data['pincode'], 'is_default'=>(bool)($data['is_default']??false),
        ];
    }

    public function store(Request $request)
    {
        $data=$this->prepared($this->validated($request));
        DB::transaction(function () use ($request,$data) {
            $user=$request->user();
            \App\Models\User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
            $none= !UserAddress::where('user_id',$user->id)->exists();
            if ($data['is_default'] || $none) {
                UserAddress::where('user_id',$user->id)->update(['is_default'=>false]);
                $data['is_default']=true;
            }
            UserAddress::create(['user_id'=>$user->id]+$data);
        },3);
        return redirect()->route('growya.addresses.index')->with('success','Address saved.');
    }

    public function update(Request $request, UserAddress $address)
    {
        abort_unless((int)$address->user_id===(int)$request->user()->id,404);
        $data=$this->prepared($this->validated($request));
        DB::transaction(function () use ($request,$address,$data) {
            \App\Models\User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            if ($data['is_default']) {
                UserAddress::where('user_id',$request->user()->id)->update(['is_default'=>false]);
            } elseif ($address->is_default) {
                // Do not leave the user without a default when unchecking the current one.
                $data['is_default']=true;
            }
            $address->update($data);
        },3);
        return redirect()->route('growya.addresses.index')->with('success','Address updated.');
    }

    public function setDefault(Request $request,UserAddress $address)
    {
        abort_unless((int)$address->user_id===(int)$request->user()->id,404);
        DB::transaction(function () use ($request,$address) {
            \App\Models\User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            UserAddress::where('user_id',$request->user()->id)->update(['is_default'=>false]);
            $address->update(['is_default'=>true]);
        },3);
        return back()->with('success','Default address updated.');
    }

    public function destroy(Request $request,UserAddress $address)
    {
        abort_unless((int)$address->user_id===(int)$request->user()->id,404);
        DB::transaction(function () use ($request,$address) {
            \App\Models\User::query()->whereKey($request->user()->id)->lockForUpdate()->firstOrFail();
            $wasDefault=(bool)$address->is_default;
            $address->delete();
            if ($wasDefault) {
                $next=UserAddress::where('user_id',$request->user()->id)->orderBy('id')->first();
                $next?->update(['is_default'=>true]);
            }
        },3);
        return back()->with('success','Address removed. Existing orders were not changed.');
    }
}
