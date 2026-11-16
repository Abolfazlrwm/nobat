<?php

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payment\Contracts\PaymentGateway;
use App\Services\Payment\PaymentManager;
use Illuminate\Support\Str;

it('refunds and cancels when the guest cancels a paid booking with the phone proof', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    settleThePayment($appointment, $payment);

    $response = $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120009999',
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);
    $this->assertSame(Payment::STATUS_REFUNDED, $payment->fresh()->status);
    $this->assertSame(Invoice::STATUS_REFUNDED, $payment->invoice->fresh()->status);

    $refund = Refund::query()->where('payment_id', $payment->id)->firstOrFail();
    $this->assertSame(Refund::STATUS_SUCCEEDED, $refund->status);
});

it('refunds and cancels when the signed-in owner cancels without re-proving the phone', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    settleThePayment($appointment, $payment);

    $mina = User::factory()->create(['phone' => '09120009999']);
    $appointment->forceFill(['user_id' => $mina->id])->save();

    $this->actingAs($mina)
        ->post(route('bookings.guest_cancel', $appointment->code))
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->assertSame(Appointment::STATUS_CANCELLED, $appointment->fresh()->status);
    $this->assertSame(Payment::STATUS_REFUNDED, $payment->fresh()->status);
});

it('does not cancel a paid booking when the gateway refund fails', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    settleThePayment($appointment, $payment);

    $manager = app(PaymentManager::class);
    $manager->extend('failing', get_class(new class implements PaymentGateway
    {
        public function request(Payment $payment, string $description, string $callbackUrl): array
        {
            throw new RuntimeException('not used');
        }

        public function verify(Payment $payment): array
        {
            return ['verified' => false, 'already_verified' => false, 'ref_id' => null, 'card_pan' => null, 'error' => 'not used'];
        }

        public function refund(Payment $payment, int $amount): array
        {
            return ['succeeded' => false, 'gateway_refund_id' => null, 'error' => 'gateway said no'];
        }
    }));
    config()->set('nobat.payment.driver', 'failing');

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120009999',
    ])->assertSessionHasErrors('customer_phone');

    // Money and slot stay put together — neither half of the deal moves.
    $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->fresh()->status);
    $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    $this->assertSame(Refund::STATUS_FAILED, $payment->refunds()->firstOrFail()->status);
});

it('still refuses a stranger cancelling a paid booking', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    settleThePayment($appointment, $payment);

    $stranger = User::factory()->create();

    $this->actingAs($stranger)
        ->post(route('bookings.guest_cancel', $appointment->code))
        ->assertSessionHasErrors('customer_phone');

    $this->assertSame(Appointment::STATUS_CONFIRMED, $appointment->fresh()->status);
    $this->assertSame(Payment::STATUS_PAID, $payment->fresh()->status);
    $this->assertSame(0, Refund::count());
});

it('never refunds twice when the cancel fires again after a successful refund', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    settleThePayment($appointment, $payment);

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120009999',
    ])->assertSessionHasNoErrors();

    // The second attempt sees a cancelled appointment — the status gate
    // refuses before any refund logic can run again.
    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120009999',
    ])->assertSessionHasErrors('customer_phone');

    $this->assertSame(1, Refund::count());
});

/**
 * Mirror what a completed gateway verify leaves behind: a paid payment,
 * a paid invoice, a confirmed appointment inside the slot window.
 */
function settleThePayment(Appointment $appointment, Payment $payment): void
{
    $payment->forceFill([
        'status' => Payment::STATUS_PAID,
        'authority' => 'A0000TEST'.Str::ulid(),
        'ref_id' => 'R'.random_int(1000, 9999),
        'paid_at' => now(),
        'verified_at' => now(),
    ])->save();

    $payment->invoice->forceFill([
        'status' => Invoice::STATUS_PAID,
        'paid_at' => now(),
    ])->save();

    $appointment->forceFill(['status' => Appointment::STATUS_CONFIRMED])->save();
}
