<?php
namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GrowyaPlanController extends Controller
{
    private function admin(): void { abort_unless(auth()->user()?->hasRole('super_admin', 'web'), 403); }

    public function index() {
        $this->admin();
        return view('growya-plans.admin', ['plans'=>Plan::orderBy('sort_order')->orderBy('id')->get()]);
    }

    public function store(Request $request) {
        $this->admin();
        $data=$request->validate([
            'name'=>['required','string','max:120'],
            'slug'=>['required','alpha_dash','max:120','unique:plans,slug'],
            'monthly_price'=>['required','numeric','min:0','max:99999999'],
            'yearly_price'=>['required','numeric','min:0','max:99999999'],
            'trial_days'=>['required','integer','min:0','max:365'],
            'sort_order'=>['nullable','integer','min:0','max:9999'],
            'is_active'=>['nullable','boolean'],
        ]);
        $data['is_active']=$request->boolean('is_active');
        $data['sort_order']=$data['sort_order']??0;
        $data['flags']=[];
        Plan::create($data);
        return back()->with('success','Plan created.');
    }

    public function update(Request $request, Plan $plan) {
        $this->admin();
        $data=$request->validate([
            'name'=>['required','string','max:120'],
            'monthly_price'=>['required','numeric','min:0','max:99999999'],
            'yearly_price'=>['required','numeric','min:0','max:99999999'],
            'trial_days'=>['required','integer','min:0','max:365'],
            'sort_order'=>['nullable','integer','min:0','max:9999'],
            'is_active'=>['nullable','boolean'],
        ]);
        $data['is_active']=$request->boolean('is_active');
        $data['sort_order']=$data['sort_order']??0;
        $plan->update($data);
        return back()->with('success','Plan updated. Existing subscriptions keep their original amount.');
    }
}
