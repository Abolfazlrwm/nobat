<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(
        private readonly Appointment $appointment,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $payment = $this->appointment->latestPayment;

        return (new MailMessage)
            ->subject('پرداخت نوبت شما انجام شد')
            ->greeting('سلام '.$this->appointment->customer_name.' عزیز،')
            ->line('پرداخت نوبت شما در «'.$this->appointment->business->name.'» با موفقیت انجام شد و نوبت شما قطعی شد.')
            ->line('خدمت: '.$this->appointment->service->name)
            ->line('زمان: '.$this->appointment->starts_at->format('Y-m-d H:i'))
            ->line('مبلغ: '.number_format($payment?->amount ?? $this->appointment->service->price).' تومان')
            ->line('کد پیگیری نوبت: '.$this->appointment->code)
            ->line('شماره تراکنش: '.($payment?->ref_id ?? '-'))
            ->action('مشاهده‌ی فاکتور', $payment?->invoice ? route('invoices.show', $payment->invoice->number) : url('/'));
    }
}
