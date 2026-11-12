<?php

namespace App\Support;

/**
 * Minimal OpenXML (xlsx) writer — pure PHP, zero dependencies.
 *
 * Builds a real ZIP archive (stored entries, crc32 per part) holding a
 * single worksheet with a bold header row, inline strings, numeric cells
 * and a right-to-left sheet view. The byte layout is shared with the
 * Pest archive-integrity tests and was validated against LibreOffice.
 */
class XlsxWriter
{
    public static function make(string $sheetName, array $header, array $rows): string
    {
        $sheet = str_replace(['[', ']', ':', '*', '?', '/', '\\'], ['(', ')', '-', '-', '-', '-', '-'], $sheetName);

        return self::zip([
            '[Content_Types].xml' => self::CONTENT_TYPES,
            '_rels/.rels' => self::RELS,
            'xl/workbook.xml' => self::workbook($sheet),
            'xl/_rels/workbook.xml.rels' => self::WORKBOOK_RELS,
            'xl/styles.xml' => self::STYLES,
            'xl/worksheets/sheet1.xml' => self::sheet($header, $rows),
        ]);
    }

    private const CONTENT_TYPES = <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
        <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
        <Default Extension="xml" ContentType="application/xml"/>
        <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
        <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
        <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
        </Types>
        XML;

    private const RELS = <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
        </Relationships>
        XML;

    private const WORKBOOK_RELS = <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
        <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
        <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
        </Relationships>
        XML;

    private const STYLES = <<<'XML'
        <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
        <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
        <fonts count="2">
        <font><sz val="11"/><name val="Calibri"/></font>
        <font><b/><sz val="11"/><name val="Calibri"/></font>
        </fonts>
        <fills count="2">
        <fill><patternFill patternType="none"/></fill>
        <fill><patternFill patternType="gray125"/></fill>
        </fills>
        <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
        <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
        <cellXfs count="2">
        <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
        <xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>
        </cellXfs>
        <cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>
        </styleSheet>
        XML;

    private static function workbook(string $sheetName): string
    {
        return <<<XML
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
            <sheets><sheet name="{$sheetName}" sheetId="1" r:id="rId1"/></sheets>
            </workbook>
            XML;
    }

    /**
     * @param  array<int, string>  $header
     * @param  array<int, array<int, string|int|float|null>>  $rows
     */
    private static function sheet(array $header, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView rightToLeft="1" workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<sheetData>'
            .'<row r="1">';

        foreach ($header as $index => $title) {
            $xml .= '<c r="'.self::columnLetter($index).'1" s="1" t="inlineStr">'
                .'<is><t>'.self::escape((string) $title).'</t></is></c>';
        }

        $xml .= '</row>';

        foreach ($rows as $rowIndex => $row) {
            $number = $rowIndex + 2;

            $xml .= '<row r="'.$number.'">';

            foreach ($row as $index => $value) {
                $ref = self::columnLetter($index).$number;

                if (is_int($value) || is_float($value)) {
                    $xml .= '<c r="'.$ref.'"><v>'.$value.'</v></c>';
                } else {
                    $xml .= '<c r="'.$ref.'" t="inlineStr">'
                        .'<is><t>'.self::escape((string) $value).'</t></is></c>';
                }
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        $position = $index + 1;

        while ($position > 0) {
            $position -= 1;
            $letter = chr(65 + ($position % 26)).$letter;
            $position = intdiv($position, 26);
        }

        return $letter;
    }

    /**
     * Pack the parts into a ZIP archive with stored (uncompressed)
     * entries — no ext-zip required, only crc32 and pack.
     *
     * @param  array<string, string>  $parts
     */
    private static function zip(array $parts): string
    {
        // Fixed DOS timestamp (2026-11-12 12:00) keeps builds reproducible.
        $dosTime = 12 << 11;
        $dosDate = ((2026 - 1980) << 9) | (11 << 5) | 12;

        $local = '';
        $central = '';
        $offset = 0;

        foreach ($parts as $name => $data) {
            $crc = crc32($data);
            $size = strlen($data);
            $nameLength = strlen($name);

            $local .= pack('VvvvvvVVVvv', 0x04034B50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0)
                .$name
                .$data;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014B50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset)
                .$name;

            $offset += 30 + $nameLength + $size;
        }

        return $local
            .$central
            .pack('VvvvvVVv', 0x06054B50, 0, 0, count($parts), count($parts), strlen($central), $offset, 0);
    }
}
