<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Service;
use App\Models\ServiceEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublicServiceEnquiryController extends Controller
{
    
private function activeService(
    \App\Models\Business $business,
    \App\Models\Service $service
): void {
    abort_unless(
        (int) $service->business_id === (int) $business->id,
        404
    );

    abort_unless(
        $business->status === 'active'
        && $business->verification_status === 'approved'
        && $service->status === 'active',
        404
    );
}


    public function create(
        Business $business,
        Service $service
    ) {
        $this->activeService($business, $service);

        return view('public.services.enquiry', compact(
            'business',
            'service'
        ));
    }

    public function store(
        Request $request,
        Business $business,
        Service $service
    ) {
        $this->activeService($business, $service);

        $data = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],

            'preferred_date' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'preferred_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'message' => [
                'nullable',
                'string',
                'max:3000',
            ],

            // Hidden honeypot field.
            'website' => [
                'nullable',
                'string',
                'max:0',
            ],
        ]);

        // Recheck current service availability at submission.
        $this->activeService($business, $service);

        DB::transaction(function () use (
            $business,
            $service,
            $data,
            $request
        ) {
            ServiceEnquiry::create([
                'business_id' => $business->id,
                'service_id' => $service->id,

                'user_id' => auth()->id(),

                'customer_name' => trim($data['customer_name']),
                'phone' => $data['phone'],

                'preferred_date' => $data['preferred_date'] ?? null,
                'preferred_time' => $data['preferred_time'] ?? null,

                'message' => $data['message'] ?? null,

                'status' => 'new',
            ]);

            // Update existing enquiry counter only if
            // the enquiries_count column was migrated.
            $service->increment('enquiries_count');
        });

        return redirect()
            ->route('public.services.enquiries.create', [
                $business,
                $service,
            ])
            ->with(
                'success',
                'Your enquiry has been submitted successfully.'
            );
    }
}
