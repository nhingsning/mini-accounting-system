<?php

namespace App\Support;

use App\Models\Quotation;

class SimplePdf
{
    /**
     * Generate a lightweight text-only PDF as a fallback when dompdf is unavailable.
     */
    public static function quotation(Quotation $q): string
    {
        $lines = [];
        $lines[] = 'Quotation: '.($q->number ?? '-');
        $lines[] = 'Date: '.optional($q->issue_date)->format('Y-m-d');
        $lines[] = 'Customer: '.($q->customer_name ?? '-');
        if ($q->customer_address) {
            $lines[] = 'Address: '.str_replace(["\r","\n"],' ', $q->customer_address);
        }
        if ($q->customer_tax_id) {
            $lines[] = 'Tax ID: '.$q->customer_tax_id;
        }
        $lines[] = 'Items:';

        $subtotal = 0.0;
        if (method_exists($q, 'items')) {
            foreach ($q->items as $idx => $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $disc  = (float)($it->discount ?? 0);
                $line  = max(($qty * $price) - $disc, 0);
                $subtotal += $line;
                $title = ($idx+1).'. '.trim((string)($it->description ?? ''));
                $lines[] = $title ?: (($idx+1).'. —');
                $lines[] = sprintf('    qty %.2f x %.2f - discount %.2f = %.2f', $qty, $price, $disc, $line);
            }
        }

        $taxRate = (float)($q->tax_rate ?? 0);
        $tax = (float)($q->tax ?? ($subtotal * ($taxRate/100)));
        $total = (float)($q->total ?? ($subtotal + $tax));

        $lines[] = 'Subtotal: '.number_format($subtotal, 2);
        $lines[] = 'Tax ('.number_format($taxRate,2).'%) : '.number_format($tax, 2);
        $lines[] = 'Total: '.number_format($total, 2);

        // --- Build a minimal PDF document ---
        $content = [];
        $y = 780;
        foreach ($lines as $line) {
            $escaped = self::escapeText($line);
            $content[] = sprintf("BT /F1 12 Tf 50 %d Td (%s) Tj ET", $y, $escaped);
            $y -= 18;
        }
        $stream = implode("\n", $content);

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = "<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i+1).' 0 obj\n'.$obj."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);

        $xref = "xref\n0 ".(count($objects)+1)."\n";
        $xref .= "0000000000 65535 f \n";
        foreach ($offsets as $off) {
            $xref .= sprintf("%010d 00000 n \n", $off);
        }

        $pdf .= $xref;
        $pdf .= "trailer\n<< /Size ".(count($objects)+1)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n".$xrefOffset."\n%%EOF\n";

        return $pdf;
    }

    private static function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\','\\(','\\)'], $text);
    }
}
