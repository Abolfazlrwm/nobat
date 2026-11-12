<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Concerns\ResolvesManagedBusiness;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Business;
use App\Support\XlsxWriter;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    use ResolvesManagedBusiness;

    public function index(Request $request): Response
    {
        $business = $this->managedBusiness($request);
        [$from, $to] = $this->resolveRange($request);

        $appointments = $this->appointmentsBetween($business->id, $from, $to);

        $byDay = $appointments->groupBy(fn (Appointment $appointment) => $appointment->starts_at->format('Y-m-d'));

        $series = [];
        for ($date = $from->copy(); $date->lte($to); $date = $date->addDay()) {
            $series[] = [
                'date' => $date->format('Y-m-d'),
                'count' => $byDay->get($date->format('Y-m-d'), collect())->count(),
            ];
        }

        $byStatus = $appointments
            ->groupBy('status')
            ->map(fn ($group, $status) => [
                'status' => $status,
                'label' => Appointment::STATUS_LABELS[$status] ?? $status,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();

        $topServices = $appointments
            ->groupBy('service_name')
            ->map(fn ($group, $name) => [
                'name' => $name,
                'count' => $group->count(),
                'revenue' => (int) $group->where('status', Appointment::STATUS_COMPLETED)->sum('service_price'),
            ])
            ->sortByDesc('count')
            ->take(5)
            ->values();

        return Inertia::render('dashboard/Reports', [
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'stats' => [
                'total' => $appointments->count(),
                'completed' => $appointments->where('status', Appointment::STATUS_COMPLETED)->count(),
                'cancelled' => $appointments->where('status', Appointment::STATUS_CANCELLED)->count(),
                'revenue' => (int) $appointments->where('status', Appointment::STATUS_COMPLETED)->sum('service_price'),
            ],
            'by_status' => $byStatus,
            'top_services' => $topServices,
            'series' => $series,
        ]);
    }

    public function export(Request $request): StreamedResponse|SymfonyResponse
    {
        $business = $this->managedBusiness($request);
        [$from, $to] = $this->resolveRange($request);

        if ($request->input('format') === 'excel') {
            return $this->exportExcel($business, $from, $to);
        }

        $filename = 'nobat-report-'.$business->slug.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($business, $from, $to) {
            $out = fopen('php://output', 'w');

            // UTF-8 BOM so Excel renders Persian text correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                __('report.track_code'),
                __('report.customer'),
                __('report.phone'),
                __('report.service'),
                __('report.staff'),
                __('report.starts_at'),
                __('report.status'),
                __('report.amount'),
            ]);

            $this->appointmentsBetween($business->id, $from, $to)
                ->each(function (Appointment $appointment) use ($out) {
                    fputcsv($out, [
                        $appointment->code,
                        $appointment->customer_name,
                        $appointment->customer_phone,
                        $appointment->service_name,
                        $appointment->staff?->name ?? '—',
                        $appointment->starts_at->format('Y-m-d H:i'),
                        $appointment->localizedStatusLabel(),
                        $appointment->service_price,
                    ]);
                });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * Build the xlsx workbook for the requested range — same rows and
     * header as the CSV export, but as a real Excel file.
     */
    private function exportExcel(Business $business, CarbonImmutable $from, CarbonImmutable $to): SymfonyResponse
    {
        $rows = $this->appointmentsBetween($business->id, $from, $to)
            ->map(fn (Appointment $appointment) => [
                $appointment->code,
                $appointment->customer_name,
                $appointment->customer_phone,
                $appointment->service_name,
                $appointment->staff?->name ?? '—',
                $appointment->starts_at->format('Y-m-d H:i'),
                $appointment->localizedStatusLabel(),
                (int) $appointment->service_price,
            ])
            ->all();

        $content = XlsxWriter::make(__('report.title'), [
            __('report.track_code'),
            __('report.customer'),
            __('report.phone'),
            __('report.service'),
            __('report.staff'),
            __('report.starts_at'),
            __('report.status'),
            __('report.amount'),
        ], $rows);

        $filename = 'nobat-report-'.$business->slug.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.xlsx';

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Appointments of one business in the range, enriched with the service
     * name/price and staff name for stats and CSV rows.
     */
    private function appointmentsBetween(int $businessId, CarbonImmutable $from, CarbonImmutable $to)
    {
        return Appointment::query()
            ->where('appointments.business_id', $businessId)
            ->whereBetween('appointments.starts_at', [$from->startOfDay(), $to->endOfDay()])
            ->join('services', 'services.id', '=', 'appointments.service_id')
            ->with('staff:id,name')
            ->select('appointments.*', 'services.name as service_name', 'services.price as service_price')
            ->orderBy('appointments.starts_at')
            ->get();
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function resolveRange(Request $request): array
    {
        $from = CarbonImmutable::parse($request->input('from', now()->subDays(29)->format('Y-m-d')));
        $to = CarbonImmutable::parse($request->input('to', now()->format('Y-m-d')));

        if ($to->lt($from)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
