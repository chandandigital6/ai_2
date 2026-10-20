<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\BusinessHour;
use App\Services\BusinessAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessHourController extends Controller
{
    /**
     * Business Opening Hours Page
     */
    public function index(Business $business)
    {
        abort_unless(app(BusinessAccessService::class)->can(auth()->user(), $business, 'business.hours.manage'), 403);

        $hours = $business->hours()
            ->get()
            ->keyBy('day_of_week');

        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return view('businesses.hours.index', compact(
            'business',
            'hours',
            'days'
        ));
    }

    /**
     * Save / Update Weekly Business Hours
     */
    public function store(Request $request, Business $business)
    {
        abort_unless(app(BusinessAccessService::class)->can(auth()->user(), $business, 'business.hours.manage'), 403);

        $validated = $request->validate([
            'hours' => ['required', 'array', 'size:7'],

            'hours.*.open_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'hours.*.close_time' => [
                'nullable',
                'date_format:H:i',
            ],

            'hours.*.is_closed' => [
                'required',
                'boolean',
            ],
        ]);

        $hours = $validated['hours'];

        // Require exactly seven days: 0-6.
        $expectedDays = range(0, 6);

        $submittedDays = array_map(
            'intval',
            array_keys($hours)
        );

        sort($submittedDays);

        if ($submittedDays !== $expectedDays) {
            throw ValidationException::withMessages([
                'hours' => 'Invalid weekly schedule.',
            ]);
        }

        foreach ($expectedDays as $day) {
            $data = $hours[$day];

            if ((bool) $data['is_closed']) {
                continue;
            }

            if (
                empty($data['open_time'])
                || empty($data['close_time'])
            ) {
                throw ValidationException::withMessages([
                    "hours.$day.open_time" => 'Opening and closing times are required.',
                ]);
            }

            if ($data['close_time'] <= $data['open_time']) {
                throw ValidationException::withMessages([
                    "hours.$day.close_time" => 'Closing time must be after opening time.',
                ]);
            }
        }

        DB::transaction(function () use ($business, $hours) {

            foreach (range(0, 6) as $day) {
                $data = $hours[$day];

                $isClosed = (bool) $data['is_closed'];

                BusinessHour::updateOrCreate(
                    [
                        'business_id' => $business->id,
                        'day_of_week' => $day,
                    ],
                    [
                        'open_time' => $isClosed
                            ? null
                            : $data['open_time'],

                        'close_time' => $isClosed
                            ? null
                            : $data['close_time'],

                        'is_closed' => $isClosed,
                    ]
                );
            }

        });

        return redirect()
            ->route('businesses.hours.index', $business)
            ->with(
                'success',
                'Business opening hours updated successfully.'
            );
    }
}
