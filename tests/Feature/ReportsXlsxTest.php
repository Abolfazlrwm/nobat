<?php

use App\Models\Appointment;
use App\Models\User;

/**
 * Walk the stored-entry local headers of our own xlsx archive and
 * collect every part — the writer emits uncompressed entries in order,
 * so a sequential walk is exact.
 */
function parseStoredZip(string $zip): array
{
    $entries = [];
    $offset = 0;
    $length = strlen($zip);

    while ($offset + 30 <= $length && substr($zip, $offset, 4) === "PK\x03\x04") {
        $nameLength = unpack('v', substr($zip, $offset + 26, 2))[1];
        $extraLength = unpack('v', substr($zip, $offset + 28, 2))[1];
        $crc = unpack('V', substr($zip, $offset + 14, 4))[1];
        $size = unpack('V', substr($zip, $offset + 18, 4))[1];
        $name = substr($zip, $offset + 30, $nameLength);
        $data = substr($zip, $offset + 30 + $nameLength + $extraLength, $size);

        $entries[$name] = ['crc' => $crc, 'size' => $size, 'data' => $data];

        $offset += 30 + $nameLength + $extraLength + $size;
    }

    return $entries;
}

it('streams a valid xlsx archive whose every entry matches its crc', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);
    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('11:00'), Appointment::STATUS_CANCELLED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $response = $this->actingAs($manager)
        ->get(route('reports.export', ['from' => now()->format('Y-m-d'), 'to' => now()->addDays(2)->format('Y-m-d'), 'format' => 'excel']));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('spreadsheetml');
    expect($response->headers->get('content-disposition'))->toContain('.xlsx');

    $zip = $response->getContent();
    expect($zip)->toStartWith("PK\x03\x04");

    // central directory right after the last local entry, EOCD at the tail
    $entries = parseStoredZip($zip);

    expect(count($entries))->toBe(6);
    expect(array_keys($entries))->toEqual([
        '[Content_Types].xml',
        '_rels/.rels',
        'xl/workbook.xml',
        'xl/_rels/workbook.xml.rels',
        'xl/styles.xml',
        'xl/worksheets/sheet1.xml',
    ]);

    foreach ($entries as $entry) {
        expect(crc32($entry['data']))->toBe($entry['crc']);
        expect($entry['size'])->toBe(strlen($entry['data']));
    }

    $centralOffset = 0;
    foreach ($entries as $entry) {
        $centralOffset += 30 + strlen($entry['data']);
    }
    // six local headers add 30 bytes each plus their names (all ascii, counted below)
    $namesLength = array_sum(array_map('strlen', array_keys($entries)));
    $centralOffset += $namesLength;

    expect(substr($zip, $centralOffset, 4))->toBe("PK\x01\x02");

    $eocd = substr($zip, -22);
    expect(substr($eocd, 0, 4))->toBe("PK\x05\x06");
    expect(unpack('V', substr($eocd, 12, 4))[1])->toBe(strlen($zip) - 22 - $centralOffset);
    expect(unpack('V', substr($eocd, 16, 4))[1])->toBe($centralOffset);

    // workbook content: persian sheet name, rtl view, real rows
    $sheet = $entries['xl/worksheets/sheet1.xml']['data'];
    expect($entries['xl/workbook.xml']['data'])->toContain('گزارش نوبت‌ها');
    expect($sheet)->toContain('rightToLeft="1"');
    expect($sheet)->toContain('کد پیگیری');
    expect($sheet)->toContain('مشتری تست');
    expect($sheet)->toContain('انجام شده');
    expect($sheet)->toContain('لغو شده');
    expect($sheet)->toContain('<v>500000</v>');
    expect(substr_count($sheet, '<row '))->toBe(3);
});

it('builds a valid workbook even for an empty range', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $response = $this->actingAs($manager)
        ->get(route('reports.export', ['from' => now()->addDays(4)->format('Y-m-d'), 'to' => now()->addDays(5)->format('Y-m-d'), 'format' => 'excel']));

    $response->assertOk();

    $entries = parseStoredZip($response->getContent());
    expect(count($entries))->toBe(6);

    $sheet = $entries['xl/worksheets/sheet1.xml']['data'];
    expect($sheet)->toContain('کد پیگیری');
    expect($sheet)->not->toContain('<row r="2"');
    expect(substr_count($sheet, '<row '))->toBe(1);

    foreach ($entries as $entry) {
        expect(crc32($entry['data']))->toBe($entry['crc']);
    }
});

it('keeps the csv export as the default format', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $response = $this->actingAs($manager)
        ->get(route('reports.export', ['from' => now()->format('Y-m-d'), 'to' => now()->addDays(2)->format('Y-m-d')]));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
    expect($response->streamedContent())->toStartWith("\xEF\xBB\xBF");
});
