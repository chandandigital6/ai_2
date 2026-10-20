<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Service;
use App\Models\ServiceEnquiry;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceEnquiryController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeBusiness(
        Business $business,
        string $permission
    ): void {
        abort_unless(
            auth()->check()
            && $this->access->can(
                auth()->user(),
                $business,
                $permission
            ),
            403
        );
    }

    private function scopedEnquiry(
        Business $business,
        ServiceEnquiry $enquiry
    ): ServiceEnquiry {
        abort_unless(
            (int) $enquiry->business_id === (int) $business->id,
            404
        );

        abort_unless(
            Service::query()
                ->where('business_id', $business->id)
                ->whereKey($enquiry->service_id)
                ->exists(),
            404
        );

        return $enquiry;
    }

    public function index(
        Request $request,
        Business $business
    ) {
        $this->authorizeBusiness(
            $business,
            'service_enquiries.view'
        );

        $query = ServiceEnquiry::query()
            ->where('business_id', $business->id)
            ->with([
                'service:id,name,business_id',
                'user:id,name',
            ]);

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('message', 'like', "%{$search}%");
            });
        }

        if (in_array(
            $request->input('status'),
            [
                'new',
                'contacted',
                'in_progress',
                'converted',
                'closed',
            ],
            true
        )) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        if ($request->filled('service_id')) {
            $serviceId = $request->integer('service_id');

            if (
                $business->services()
                    ->whereKey($serviceId)
                    ->exists()
            ) {
                $query->where('service_id', $serviceId);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('date_from')) {
            $request->validate([
                'date_from' => ['date_format:Y-m-d'],
            ]);

            $query->whereDate(
                'created_at',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $request->validate([
                'date_to' => ['date_format:Y-m-d'],
            ]);

            $query->whereDate(
                'created_at',
                '<=',
                $request->input('date_to')
            );
        }

        $enquiries = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $services = $business->services()
            ->orderBy('name')
            ->get(['id', 'name']);

        $counts = ServiceEnquiry::query()
            ->where('business_id', $business->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = $counts->sum();

        return view('services.enquiries.index', compact(
            'business',
            'enquiries',
            'services',
            'counts',
            'total'
        ));
    }

    public function show(
        Business $business,
        ServiceEnquiry $enquiry
    ) {
        $this->authorizeBusiness(
            $business,
            'service_enquiries.view'
        );

        $enquiry = $this->scopedEnquiry(
            $business,
            $enquiry
        );

        $enquiry->load([
            'service:id,name,business_id',
            'user:id,name',
            'followUps' => fn ($query) => $query
                ->with([
                    'creator:id,name',
                    'completedBy:id,name',
                ])
                ->latest('id'),
        ]);

        return view('services.enquiries.show', compact(
            'business',
            'enquiry'
        ));
    }

    public function updateStatus(
        Request $request,
        Business $business,
        ServiceEnquiry $enquiry
    ) {
        $this->authorizeBusiness(
            $business,
            'service_enquiries.manage'
        );

        $enquiry = $this->scopedEnquiry(
            $business,
            $enquiry
        );

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'new',
                    'contacted',
                    'in_progress',
                    'converted',
                    'closed',
                ]),
            ],
        ]);

        $enquiry->update([
            'status' => $data['status'],
        ]);

        return back()->with(
            'success',
            'Enquiry status updated successfully.'
        );
    }
}
