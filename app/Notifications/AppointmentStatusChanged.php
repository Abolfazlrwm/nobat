<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class AppointmentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Appointment $appointment,
    ) {
        // The database payload is built on the queue worker — pin the locale
        // of the request that changed the status and restore it in toArray().
        $this->locale = app()->getLocale();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        App::setLocale($this->locale ?? 'fa');

        return [
            'appointment_code' => $this->appointment->code,
            'status' => $this->appointment->status,
            'status_label' => $this->appointment->localizedStatusLabel(),
            'starts_at' => $this->appointment->starts_at->format('Y-m-d\TH:i:s'),
            'business_name' => $this->appointment->business->name,
            'service_name' => $this->appointment->service->name,
            'title' => __('notifications.appointment_status'),
        ];
    }
}
