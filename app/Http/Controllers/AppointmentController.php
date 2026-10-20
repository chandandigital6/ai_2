<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Service;
use App\Models\ServiceEnquiry;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    public function __construct(
        private BusinessAccessService $access
    ) {}

    private function authorizeBusiness(
        Business $business,
        string $permission
    ): void {
        abort_unless(
            auth()->check() &&
            $this->access->can(
                auth()->user(),
                $business,
                $permission
            ),
            403
        );
    }

    private function checkAppointment(
        Business $business,
        Appointment $appointment
    ): void {
        abort_unless(
            (int) $appointment->business_id === (int) $business->id,
            404
        );
    }

    private function services(Business $business)
    {
        return Service::query()
            ->where('business_id', $business->id)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'duration_minutes']);
    }

    public function index(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'appointments.view');

        $request->validate([
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'cancelled',
                ]),
            ],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'service_id' => [
                'nullable',
                Rule::exists('services', 'id')
                    ->where('business_id', $business->id),
            ],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $query = Appointment::query()
            ->where('business_id', $business->id)
            ->with('service:id,name');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));

            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('service_id')) {
            $query->where('service_id', $request->input('service_id'));
        }

        if ($request->filled('date_from')) {
            $query->whereDate(
                'appointment_date',
                '>=',
                $request->input('date_from')
            );
        }

        if ($request->filled('date_to')) {
            $query->whereDate(
                'appointment_date',
                '<=',
                $request->input('date_to')
            );
        }

        $appointments = $query
            ->orderByDesc('appointment_date')
            ->orderBy('appointment_time')
            ->paginate(20)
            ->withQueryString();

        $base = Appointment::query()
            ->where('business_id', $business->id);

        $today = now()->toDateString();

        $stats = [
            'total' => (clone $base)->count(),

            'today' => (clone $base)
                ->whereDate('appointment_date', $today)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),

            'upcoming' => (clone $base)
                ->where(function ($q) use ($today) {
                    $q->whereDate('appointment_date', '>', $today)
                        ->orWhere(function ($sameDay) use ($today) {
                            $sameDay->whereDate(
                                'appointment_date',
                                $today
                            )->whereTime(
                                'appointment_time',
                                '>=',
                                now()->format('H:i:s')
                            );
                        });
                })
                ->whereIn('status', ['pending', 'confirmed'])
                ->count(),

            'pending' => (clone $base)
                ->where('status', 'pending')
                ->count(),

            'completed' => (clone $base)
                ->where('status', 'completed')
                ->count(),

            'cancelled' => (clone $base)
                ->where('status', 'cancelled')
                ->count(),
        ];

        $services = $this->services($business);

        return view('appointments.index', compact(
            'business',
            'appointments',
            'stats',
            'services'
        ));
    }

    public function create(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'appointments.manage');

        $services = $this->services($business);
        $enquiry = null;

        if ($request->filled('enquiry_id')) {
            $enquiry = ServiceEnquiry::query()
                ->where('business_id', $business->id)
                ->whereKey($request->integer('enquiry_id'))
                ->firstOrFail();
        }

        return view('appointments.form', [
            'business' => $business,
            'appointment' => new Appointment,
            'services' => $services,
            'enquiry' => $enquiry,
        ]);
    }

    private function validated(
        Request $request,
        Business $business
    ): array {
        return $request->validate([
            'service_id' => [
                'required',
                Rule::exists('services', 'id')
                    ->where('business_id', $business->id)
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
            ],

            'service_enquiry_id' => [
                'nullable',
                Rule::exists('service_enquiries', 'id')
                    ->where('business_id', $business->id),
            ],

            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'customer_phone' => [
                'required',
                'regex:/^[6-9][0-9]{9}$/',
            ],

            'appointment_date' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:today',
            ],

            'appointment_time' => [
                'required',
                'date_format:H:i',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
    }

    public function store(Request $request, Business $business)
    {
        $this->authorizeBusiness($business, 'appointments.manage');

        $data = $this->validated($request, $business);

        $enquiry = null;

        if (! empty($data['service_enquiry_id'])) {
            $enquiry = ServiceEnquiry::query()
                ->where('business_id', $business->id)
                ->whereKey($data['service_enquiry_id'])
                ->firstOrFail();

            if ((int) $enquiry->service_id !== (int) $data['service_id']) {
                throw ValidationException::withMessages([
                    'service_id' => 'Selected service does not match the enquiry.',
                ]);
            }
        }

        $appointmentDateTime = Carbon::parse(
            $data['appointment_date'].' '.$data['appointment_time'],
            config('app.timezone')
        );

        if ($appointmentDateTime->isPast()) {
            throw ValidationException::withMessages([
                'appointment_time' => 'Please select a future appointment time.',
            ]);
        }

        /*
         * Lock the service row to serialize new bookings
         * for the same service.
         *
         * Rule for MVP:
         * One active appointment per service at an exact
         * date and time. Duration overlap is not yet checked.
         */
        $appointment = DB::transaction(function () use (
            $business,
            $data,
            $enquiry
        ) {
            Service::query()
                ->where('business_id', $business->id)
                ->where('status', 'active')
                ->whereKey($data['service_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $exists = Appointment::query()
                ->where('business_id', $business->id)
                ->where('service_id', $data['service_id'])
                ->whereDate('appointment_date', $data['appointment_date'])
                ->whereTime('appointment_time', $data['appointment_time'])
                ->whereIn('status', ['pending', 'confirmed'])
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'appointment_time' => 'This service already has an active booking at the selected time.',
                ]);
            }

            return Appointment::create([
                'business_id' => $business->id,
                'service_id' => $data['service_id'],
                'service_enquiry_id' => $enquiry?->id,
                'customer_id' => $enquiry?->user_id,
                'customer_name' => trim($data['customer_name']),
                'customer_phone' => $data['customer_phone'],
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
            ]);
        }, 3);

        return redirect()
            ->route('businesses.appointments.show', [
                $business,
                $appointment,
            ])
            ->with('success', 'Appointment created successfully.');
    }

    public function show(
        Business $business,
        Appointment $appointment
    ) {
        $this->checkAppointment($business, $appointment);
        $this->authorizeBusiness($business, 'appointments.view');

        $appointment->load([
            'service:id,name,business_id',
            'enquiry:id,business_id,service_id,customer_name,phone',
            'customer:id,name',
        ]);

        return view('appointments.show', compact(
            'business',
            'appointment'
        ));
    }

    public function updateStatus(
        Request $request,
        Business $business,
        Appointment $appointment
    ) {
        $this->checkAppointment($business, $appointment);
        $this->authorizeBusiness($business, 'appointments.manage');

        $data = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'confirmed',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        // Do not reactivate completed/cancelled bookings.
        if (
            in_array($appointment->status, ['completed', 'cancelled'], true)
            && $data['status'] !== $appointment->status
        ) {
            throw ValidationException::withMessages([
                'status' => 'Completed or cancelled appointments cannot be reopened.',
            ]);
        }

        DB::transaction(function () use ($business, $appointment, $data) {
            Service::query()
                ->where('business_id', $business->id)
                ->whereKey($appointment->service_id)
                ->lockForUpdate()
                ->firstOrFail();

            $locked = Appointment::query()
                ->where('business_id', $business->id)
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                in_array($locked->status, ['completed', 'cancelled'], true)
                && $data['status'] !== $locked->status
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Appointment is already finalized.',
                ]);
            }

            $locked->update([
                'status' => $data['status'],
            ]);
        }, 3);

        return back()->with(
            'success',
            'Appointment status updated successfully.'
        );
    }
}
