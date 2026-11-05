<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AppointmentStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

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
        return [
            'appointment_code' => $this->appointment->code,
            'status' => $this->appointment->status,
            'status_label' => Appointment::STATUS_LABELS[$this->appointment->status] ?? $this->appointment->status,
            'starts_at' => $this->appointment->starts_at->format('Y-m-d\TH:i:s'),
            'business_name' => $this->appointment->business->name,
            'service_name' => $this->appointment->service->name,
            'title' => 'وضعیت نوبت شما تغییر کرد',
        ];
    }
}
