<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\ServiceEnquiry;
use App\Models\ServiceEnquiryFollowUp;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ServiceEnquiryFollowUpController extends Controller
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

    private function checkEnquiry(
        Business $business,
        ServiceEnquiry $enquiry
    ): void {
        abort_unless(
            (int) $enquiry->business_id === (int) $business->id,
            404
        );
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'service_enquiries.view');

        $filter = $request->input('filter', 'pending');

        if (! in_array(
            $filter,
            ['pending', 'overdue', 'completed', 'all'],
            true
        )) {
            $filter = 'pending';
        }

        $query = ServiceEnquiryFollowUp::query()
            ->whereHas('enquiry', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })
            ->with([
                'enquiry:id,business_id,service_id,customer_name,phone,status',
                'enquiry.service:id,name',
                'creator:id,name',
                'completedBy:id,name',
            ])
            ->whereNotNull('follow_up_at');

        if ($filter === 'pending') {
            $query->pending();
        }

        if ($filter === 'overdue') {
            $query->overdue();
        }

        if ($filter === 'completed') {
            $query->whereNotNull('completed_at');
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));

            $query->whereHas('enquiry', function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->where('customer_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        if ($filter === 'completed') {
            $query->orderByDesc('completed_at');
        } else {
            $query->orderBy('follow_up_at');
        }

        $followUps = $query
            ->paginate(20)
            ->withQueryString();

        $baseQuery = ServiceEnquiryFollowUp::query()
            ->whereHas('enquiry', function ($q) use ($business) {
                $q->where('business_id', $business->id);
            })
            ->whereNotNull('follow_up_at');

        $counts = [
            'pending' => (clone $baseQuery)
                ->whereNull('completed_at')
                ->count(),

            'overdue' => (clone $baseQuery)
                ->whereNull('completed_at')
                ->where('follow_up_at', '<', now())
                ->count(),

            'completed' => (clone $baseQuery)
                ->whereNotNull('completed_at')
                ->count(),

            'all' => (clone $baseQuery)->count(),
        ];

        return view('services.follow_ups.index', compact(
            'business',
            'followUps',
            'counts',
            'filter'
        ));
    }

    public function store(
        Request $request,
        Business $business,
        ServiceEnquiry $enquiry
    ) {
        $this->checkEnquiry($business, $enquiry);

        $this->authorizeBusiness(
            $business,
            'service_enquiries.manage'
        );

        $data = $request->validate([
            'note' => [
                'required',
                'string',
                'max:10000',
            ],

            'follow_up_at' => [
                'nullable',
                'date_format:Y-m-d\TH:i',
            ],
        ]);

        $followUpAt = null;

        if (! empty($data['follow_up_at'])) {
            $followUpAt = Carbon::createFromFormat(
                'Y-m-d\TH:i',
                $data['follow_up_at'],
                config('app.timezone')
            );

            if ($followUpAt->lessThanOrEqualTo(now())) {
                throw ValidationException::withMessages([
                    'follow_up_at' => 'Follow-up date and time must be in the future.',
                ]);
            }
        }

        $enquiry->followUps()->create([
            'created_by' => auth()->id(),
            'note' => trim($data['note']),
            'follow_up_at' => $followUpAt,
            'completed_at' => null,
            'completed_by' => null,
        ]);

        return redirect()
            ->route(
                'businesses.service-enquiries.show',
                [$business, $enquiry]
            )
            ->with(
                'success',
                $followUpAt
                    ? 'Note and follow-up scheduled successfully.'
                    : 'Enquiry note added successfully.'
            );
    }

    public function complete(
        Business $business,
        ServiceEnquiry $enquiry,
        ServiceEnquiryFollowUp $followUp
    ) {
        $this->checkEnquiry($business, $enquiry);

        $this->authorizeBusiness(
            $business,
            'service_enquiries.manage'
        );

        abort_unless(
            (int) $followUp->service_enquiry_id === (int) $enquiry->id,
            404
        );

        abort_unless(
            $followUp->follow_up_at !== null,
            422,
            'This note is not a scheduled follow-up.'
        );

        DB::transaction(function () use ($followUp) {
            $locked = ServiceEnquiryFollowUp::query()
                ->whereKey($followUp->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->completed_at !== null) {
                return;
            }

            $locked->update([
                'completed_at' => now(),
                'completed_by' => auth()->id(),
            ]);
        });

        return back()->with(
            'success',
            'Follow-up marked as completed.'
        );
    }
}
