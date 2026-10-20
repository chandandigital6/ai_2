<?php

namespace App\Observers;

use App\Models\Appointment;
use App\Models\Business;
use App\Models\Review;
use App\Models\ServiceEnquiry;
use App\Services\GrowyaNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class GrowyaEventNotificationObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private GrowyaNotificationService $notify) {}

    public function created(object $model): void
    {
        if ($model instanceof ServiceEnquiry) {
            $this->notify->send(
                $this->notify->ownerOf((int) $model->business_id),
                'service_enquiry', 'New service enquiry',
                'A new enquiry has arrived for your business.',
                "/businesses/{$model->business_id}/service-enquiries/{$model->id}",
                (int) $model->business_id
            );
        } elseif ($model instanceof Appointment) {
            $this->notify->send(
                $this->notify->ownerOf((int) $model->business_id),
                'appointment_created', 'New appointment',
                'A service appointment has been booked.',
                "/businesses/{$model->business_id}/appointments/{$model->id}",
                (int) $model->business_id
            );
        } elseif ($model instanceof Review) {
            $this->notify->send(
                $this->notify->superAdmins(),
                'review_pending', 'New review to moderate',
                'A customer review is awaiting moderation.',
                '/admin/business-reviews',
                (int) $model->business_id
            );
        }
    }

    public function updated(object $model): void
    {
        if ($model instanceof Business && $model->wasChanged('verification_status')) {
            $status = (string) $model->verification_status;
            $this->notify->send(
                $this->notify->ownerOf((int) $model->id),
                'business_verification', 'Business verification updated',
                "Your business verification status is now {$status}.",
                "/b/{$model->slug}", (int) $model->id
            );
        } elseif ($model instanceof Appointment && $model->wasChanged('status')) {
            $this->notify->send(
                $this->notify->ownerOf((int) $model->business_id),
                'appointment_status', 'Appointment status updated',
                "Appointment #{$model->id} status: {$model->status}.",
                "/businesses/{$model->business_id}/appointments/{$model->id}",
                (int) $model->business_id
            );
        } elseif ($model instanceof Review && $model->wasChanged('status')) {
            $this->notify->send(
                [(int) $model->user_id],
                'review_status', 'Your review status changed',
                "Your review is now {$model->status}.",
                "/b/" . ($model->business?->slug ?? '') . '/reviews',
                (int) $model->business_id
            );
        }
    }
}
