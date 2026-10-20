<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessEnquiry;
use App\Models\BusinessEnquiryNote;
use App\Models\Product;
use App\Services\BusinessAccessService;
use App\Services\GrowyaNotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductEnquiryController extends Controller
{
    public function __construct(private BusinessAccessService $access) {}

    private function authorizeBusiness(Business $business, string $permission): void
    {
        abort_unless(auth()->check() && $this->access->can(auth()->user(), $business, $permission), 403);
    }

    private function check(Business $business, BusinessEnquiry $enquiry): void
    {
        abort_unless((int) $enquiry->business_id === (int) $business->id && $enquiry->product_id !== null, 404);
    }

    private function publicProduct(Business $business, Product $product): void
    {
        abort_unless($business->status === 'active' && $business->verification_status === 'approved' &&
            (int) $product->business_id === (int) $business->id && $product->status === 'active' && ! $product->trashed(), 404);
    }

    public function create(Business $business, Product $product)
    {
        $this->publicProduct($business, $product);

        return view('public.products.enquiry', compact('business', 'product'));
    }

    public function store(Request $request, Business $business, Product $product, GrowyaNotificationService $notifications)
    {
        $this->publicProduct($business, $product);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'regex:/^[6-9][0-9]{9}$/'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'message' => ['nullable', 'string', 'max:3000'],
            'website' => ['nullable', 'size:0'],
        ]);
        $enquiry = BusinessEnquiry::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'service_id' => null,
            'user_id' => auth()->id(),
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'quantity' => $data['quantity'],
            'source' => 'product',
            'message' => $data['message'] ?? null,
            'status' => 'new',
        ]);
        // Notification failure must not cause customer to submit a duplicate enquiry.
        try {
            $notifications->send(
                $notifications->ownerOf((int) $business->id),
                'product_enquiry',
                'New product enquiry',
                'New enquiry for '.$product->name,
                route('businesses.product-enquiries.show', [$business, $enquiry], false),
                (int) $business->id
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('public.products.enquiry.create', [$business, $product])->with('success', 'Your enquiry has been sent successfully.');
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'product_enquiries.view');
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['new', 'contacted', 'converted', 'closed'])],
        ]);
        $q = BusinessEnquiry::query()->where('business_id', $business->id)->whereNotNull('product_id')->with('product:id,name,business_id');
        if (! empty($filters['search'])) {
            $s = $filters['search'];
            $q->where(function ($x) use ($s) {
                $x->where('name', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%{$s}%"));
            });
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        $enquiries = $q->latest('id')->paginate(20)->withQueryString();

        return view('businesses.product-enquiries.index', compact('business', 'enquiries'));
    }

    public function show(Business $business, BusinessEnquiry $enquiry)
    {
        $this->authorizeBusiness($business, 'product_enquiries.view');
        $this->check($business, $enquiry);
        $enquiry->load(['product:id,name,business_id', 'user:id,name']);
        $notes = BusinessEnquiryNote::query()->where('business_enquiry_id', $enquiry->id)->with('creator:id,name')->latest('id')->get();

        return view('businesses.product-enquiries.show', compact('business', 'enquiry', 'notes'));
    }

    public function status(Request $request, Business $business, BusinessEnquiry $enquiry)
    {
        $this->authorizeBusiness($business, 'product_enquiries.manage');
        $this->check($business, $enquiry);
        $data = $request->validate(['status' => ['required', Rule::in(['new', 'contacted', 'converted', 'closed'])]]);
        $enquiry->update(['status' => $data['status']]);

        return back()->with('success', 'Status updated.');
    }

    public function note(Request $request, Business $business, BusinessEnquiry $enquiry)
    {
        $this->authorizeBusiness($business, 'product_enquiries.manage');
        $this->check($business, $enquiry);
        $data = $request->validate(['note' => ['required', 'string', 'max:10000'], 'follow_up_at' => ['nullable', 'date_format:Y-m-d\\TH:i', 'after:now']]);
        BusinessEnquiryNote::create([
            'business_enquiry_id' => $enquiry->id,
            'created_by' => auth()->id(),
            'note' => trim($data['note']),
            'follow_up_at' => $data['follow_up_at'] ?? null,
        ]);

        return back()->with('success', 'Note saved.');
    }

    public function complete(Business $business, BusinessEnquiry $enquiry, BusinessEnquiryNote $note)
    {
        $this->authorizeBusiness($business, 'product_enquiries.manage');
        $this->check($business, $enquiry);
        abort_unless((int) $note->business_enquiry_id === (int) $enquiry->id && $note->follow_up_at !== null, 404);
        BusinessEnquiryNote::whereKey($note->id)->whereNull('completed_at')->update(['completed_at' => now()]);

        return back()->with('success', 'Follow-up completed.');
    }
}
