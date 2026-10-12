<?php
if (!function_exists('peso')) {
    function peso($n): string { return '₱' . number_format((float) $n, 2); }
}
if (!function_exists('qty')) {
    function qty($n): string {
        $s = rtrim(rtrim(number_format((float) $n, 3, '.', ','), '0'), '.');
        return $s === '' ? '0' : $s;
    }
}
if (!function_exists('dt')) {
    function dt($d, string $f = 'M j, Y, g:i A'): string {
        return $d ? \Illuminate\Support\Carbon::parse($d)->format($f) : '—';
    }
}
if (!function_exists('csv_download')) {
    function csv_download(string $name, array $headers, iterable $rows) {
        return response()->streamDownload(function () use ($headers, $rows) {
            $o = fopen('php://output', 'w');
            fputcsv($o, $headers);
            foreach ($rows as $r) fputcsv($o, $r);
            fclose($o);
        }, $name, ['Content-Type' => 'text/csv']);
    }
}
