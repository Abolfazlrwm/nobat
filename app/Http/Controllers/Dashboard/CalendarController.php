<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Models\Staff;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Models\WorkingHour;
use App\Support\Jalali;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The visual Jalali calendar of the panel: one month of appointments as
 * per-day aggregates (counts per status, busy minutes, waitlist demand)
 * plus the full timeline of a selected day.
 */
class CalendarController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $businessId = $user->isSuperAdmin() ? $request->integer('business_id') ?: null : null;

        // The month is addressed in Jalali coordinates, clamped into the
        // range the converter algorithm supports.
        [$todayJy, $todayJm, $todayJd] = Jalali::fromCarbon(today());

        $jy = $request->filled('jy') ? $request->integer('jy') : $todayJy;
        $jm = $request->filled('jm') ? $request->integer('jm') : $todayJm;
        $jy = min(max($jy, 1300), 1500);
        $jm = min(max($jm, 1), 12);

        $monthLength = Jalali::monthLength($jy, $jm);
        $firstDay = Jalali::toCarbon($jy, $jm, 1);
        $lastDay = Jalali::toCarbon($jy, $jm, $monthLength);

        // The selected day must belong to the rendered month.
        $selectedDate = $request->filled('date')
            ? Carbon::parse($request->string('date'))
            : ($jy === $todayJy && $jm === $todayJm ? today() : $firstDay);
        if ($selectedDate->lt($firstDay) || $selectedDate->gt($lastDay->endOfDay())) {
            $selectedDate = $firstDay;
        }
        $selectedDate = $selectedDate->startOfDay();

        $appointments = $this->monthAppointments($request, $businessId, $firstDay, $lastDay)
            ->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'));

        $waitlist = $this->monthWaitlist($user, $businessId, $firstDay, $lastDay)
            ->groupBy(fn (WaitlistEntry $entry) => $entry->desired_date->format('Y-m-d'));

        $days = [];
        for ($jd = 1; $jd <= $monthLength; $jd++) {
            $date = Jalali::toCarbon($jy, $jm, $jd);
            $key = $date->format('Y-m-d');
            $dayAppointments = $appointments->get($key, collect());
            $dayWaitlist = $waitlist->get($key, collect());

            $counts = [];
            foreach (Appointment::STATUSES as $status) {
                $counts[$status] = $dayAppointments->where('status', $status)->count();
            }

            $days[] = [
                'date' => $key,
                'jd' => $jd,
                'weekday' => WorkingHour::weekdayFromCarbon($date),
                'counts' => $counts,
                'blocking_count' => $dayAppointments->whereIn('status', Appointment::BLOCKING_STATUSES)->count(),
                'busy_minutes' => (int) $dayAppointments
                    ->whereIn('status', Appointment::BLOCKING_STATUSES)
                    ->sum(fn (Appointment $appointment) => $appointment->service?->duration_minutes ?? 0),
                'waitlist_count' => $dayWaitlist
                    ->whereIn('status', [WaitlistEntry::STATUS_WAITING, WaitlistEntry::STATUS_NOTIFIED])
                    ->count(),
                'is_today' => $jy === $todayJy && $jm === $todayJm && $jd === $todayJd,
                'is_past' => $date->lt(today()),
            ];
        }

        $selectedKey = $selectedDate->format('Y-m-d');
        $selectedAppointments = $appointments->get($selectedKey, collect())
            ->sortBy('starts_at')
            ->values()
            ->map(fn (Appointment $appointment) => $this->present($appointment));

        $selectedWaitlist = $waitlist->get($selectedKey, collect())
            ->sortBy('created_at')
            ->values()
            ->map(fn (WaitlistEntry $entry) => [
                'id' => $entry->id,
                'customer_name' => $entry->customer_name,
                'customer_phone' => $entry->customer_phone,
                'status' => $entry->status,
                'status_label' => WaitlistEntry::STATUS_LABELS[$entry->status] ?? $entry->status,
            ]);

        return Inertia::render('dashboard/Calendar', [
            'filters' => [
                'jy' => $jy,
                'jm' => $jm,
                'date' => $selectedKey,
            ],
            'month' => [
                'jy' => $jy,
                'jm' => $jm,
                'length' => $monthLength,
                'first_weekday' => WorkingHour::weekdayFromCarbon($firstDay),
            ],
            'today_jalali' => ['jy' => $todayJy, 'jm' => $todayJm, 'jd' => $todayJd],
            'days' => $days,
            'selected' => [
                'date' => $selectedKey,
                'jd' => Jalali::fromCarbon($selectedDate)[2],
                'weekday' => WorkingHour::weekdayFromCarbon($selectedDate),
                'appointments' => $selectedAppointments,
                'waitlist' => $selectedWaitlist,
            ],
            'businesses' => $user->isSuperAdmin()
                ? Business::query()->orderBy('id')->get(['id', 'name'])->toArray()
                : [],
            'business_id' => $businessId,
        ]);
    }

    /**
     * Every appointment of the rendered month, scoped exactly like the
     * appointments page (super-admins all, managers their business, staff
     * their own shifts).
     */
    private function monthAppointments(Request $request, ?int $businessId, CarbonImmutable $firstDay, CarbonImmutable $lastDay)
    {
        $query = Appointment::query()
            ->visibleTo($request->user())
            ->whereBetween('starts_at', [$firstDay->startOfDay(), $lastDay->endOfDay()])
            ->with('service:id,duration_minutes');

        if ($businessId) {
            $query->where('business_id', $businessId);
        }

        return $query->get();
    }

    /**
     * Waitlist demand of the rendered month, scoped by the businesses the
     * user manages (the calendar never leaks another business's queue).
     */
    private function monthWaitlist($user, ?int $businessId, CarbonImmutable $firstDay, CarbonImmutable $lastDay)
    {
        $query = WaitlistEntry::query()
            ->whereBetween('desired_date', [$firstDay->format('Y-m-d'), $lastDay->format('Y-m-d')]);

        if ($user->isSuperAdmin()) {
            if ($businessId) {
                $query->where('business_id', $businessId);
            }
        } elseif ($user->hasRole(User::ROLE_MANAGER)) {
            $query->whereIn('business_id', Business::query()->where('owner_id', $user->id)->pluck('id'));
        } else {
            $query->whereIn('business_id', Staff::query()->where('user_id', $user->id)->pluck('business_id'));
        }

        return $query->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function present(Appointment $appointment): array
    {
        return [
            'id' => $appointment->id,
            'code' => $appointment->code,
            'customer_name' => $appointment->customer_name,
            'customer_phone' => $appointment->customer_phone,
            'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
            'status' => $appointment->status,
            'status_label' => Appointment::STATUS_LABELS[$appointment->status] ?? $appointment->status,
            'service_name' => $appointment->service?->name,
            'staff_name' => $appointment->staff?->name,
            'business_name' => $appointment->business?->name,
        ];
    }
}
