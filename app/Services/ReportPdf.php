<?php
namespace App\Services;

/**
 * Dependency-free, paginated printable PDF for business reports.
 * Uses standard PDF Helvetica fonts and ASCII-safe text for broad compatibility.
 */
class ReportPdf
{
    private static function clean($value): string
    {
        $value = strip_tags((string) ($value ?? ''));
        $value = str_replace(["\r", "\n", "\t", '₱', '—', '–', '·'], [' ', ' ', ' ', 'PHP ', '-', '-', '-'], $value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: '';
        return preg_replace('/[^\x20-\x7E]/', '', $value);
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    public static function download(string $filename, string $title, array $headers, iterable $rows, array $details = [])
    {
        $lines = [];
        $add = function (string $text, int $size = 10, bool $bold = false, int $indent = 0) use (&$lines) {
            $lines[] = [$text, $size, $bold, $indent];
        };
        $add('TUKODTRACK  /  BUSINESS REPORT', 10, true);
        $add($title, 17, true);
        foreach ($details as $key => $value) {
            $add(self::clean($key) . ': ' . self::clean($value), 9);
        }
        $add(str_repeat('-', 102), 9);
        $add(implode('   |   ', array_map([self::class, 'clean'], $headers)), 9, true);
        $add(str_repeat('-', 102), 9);
        $count = 0;
        foreach ($rows as $row) {
            $count++;
            $text = implode('   |   ', array_map([self::class, 'clean'], (array) $row));
            foreach (str_split($text, 102) as $part) {
                $add($part, 8);
            }
        }
        if (!$count) $add('No records for the selected report.', 10);
        $add(str_repeat('-', 102), 9);
        $add('Total records: ' . $count, 9, true);

        $pages = [];
        $current = [];
        $y = 785;
        foreach ($lines as [$text, $size, $bold, $indent]) {
            $height = $size + 7;
            if ($y - $height < 50) {
                $pages[] = $current;
                $current = [];
                $y = 785;
            }
            $current[] = [$text, $size, $bold, $indent, $y];
            $y -= $height;
        }
        if ($current) $pages[] = $current;

        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $kids = [];
        $id = 5;
        foreach ($pages as $pageIndex => $page) {
            $pageId = $id++;
            $streamId = $id++;
            $kids[] = $pageId . ' 0 R';
            $commands = [];
            foreach ($page as [$text, $size, $bold, $indent, $yy]) {
                $commands[] = sprintf('BT /F%d %d Tf 40 %d Td (%s) Tj ET', $bold ? 2 : 1, $size, $yy, self::escape($text));
            }
            $commands[] = sprintf('BT /F1 8 Tf 40 30 Td (Page %d of %d) Tj ET', $pageIndex + 1, count($pages));
            $stream = implode("\n", $commands) . "\n";
            $objects[$streamId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $streamId 0 R >>";
        }
        // A4 landscape, with standard fonts.
        foreach ($pages as &$page) {
            foreach ($page as &$line) $line[4] = 550 - (785 - $line[4]) * 0.65;
        }
        unset($page, $line);
        // Recreate streams using landscape page coordinates.
        $id = 5;
        foreach ($pages as $pageIndex => $page) {
            $pageId = $id++;
            $streamId = $id++;
            $commands = [];
            foreach ($page as [$text, $size, $bold, $indent, $yy]) {
                $commands[] = sprintf('BT /F%d %d Tf 35 %.1f Td (%s) Tj ET', $bold ? 2 : 1, $size, $yy, self::escape($text));
            }
            $commands[] = sprintf('BT /F1 8 Tf 35 20 Td (Page %d of %d) Tj ET', $pageIndex + 1, count($pages));
            $stream = implode("\n", $commands) . "\n";
            $objects[$streamId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream';
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objects);
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        $max = max(array_keys($objects));
        for ($i = 1; $i <= $max; $i++) {
            $offsets[$i] = strlen($pdf);
            $pdf .= $i . " 0 obj\n" . $objects[$i] . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . basename($filename) . '"',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
