<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\App;

class PaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
    ) {
        // The mail is rendered on the queue worker — pin the locale of the
        // request that resolved the payment and restore it in toMail().
        $this->locale = app()->getLocale();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        App::setLocale($this->locale ?? 'fa');

        $payment = $this->appointment->latestPayment;

        return (new MailMessage)
            ->subject(__('mails.payment_subject'))
            ->greeting(__('mails.payment_greeting', ['name' => $this->appointment->customer_name]))
            ->line(__('mails.payment_business', ['business' => $this->appointment->business->name]))
            ->line(__('mails.payment_service', ['service' => $this->appointment->service->name]))
            ->line(__('mails.payment_time', ['time' => $this->appointment->starts_at->format('Y-m-d H:i')]))
            ->line(__('mails.payment_amount', ['amount' => number_format($payment?->amount ?? $this->appointment->service->price)]))
            ->line(__('mails.payment_code', ['code' => $this->appointment->code]))
            ->line(__('mails.payment_ref', ['ref' => $payment?->ref_id ?? '-']))
            ->action(__('mails.payment_action'), $payment?->invoice ? route('invoices.show', $payment->invoice->number) : url('/'));
    }
}
