<?php

namespace App\Support;

use App\Models\Invoice;
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
        $nl = "\r\n";
        $stream = "BT{$nl}/F1 12 Tf{$nl}50 780 Td{$nl}";
        foreach ($lines as $line) {
            $stream .= '('.self::escapeText($line).") Tj{$nl}0 -18 Td{$nl}";
        }
        $stream .= "ET{$nl}";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = "<< /Length ".strlen($stream)." >>{$nl}stream{$nl}".$stream."endstream{$nl}";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4{$nl}%".chr(0xE2).chr(0xE3).chr(0xCF).chr(0xD3).$nl; // binary comment to satisfy PDF readers
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i + 1).' 0 obj'.$nl.$obj."endobj{$nl}";
        }

        $pdf .= $nl; // spacer before xref improves compatibility
        $xrefOffset = strlen($pdf);

        $pdf .= 'xref'.$nl;
        $pdf .= '0 '.(count($objects) + 1).$nl;
        $pdf .= '0000000000 65535 f '.$nl;
        foreach ($offsets as $off) {
            $pdf .= sprintf('%010d 00000 n '.$nl, $off);
        }

        $pdf .= 'trailer'.$nl;
        $pdf .= '<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'.$nl;
        $pdf .= 'startxref'.$nl.$xrefOffset.$nl;
        $pdf .= '%%EOF'.$nl;

        return $pdf;
    }

    public static function invoice(Invoice $invoice): string
    {
        $lines = [];
        $lines[] = 'Invoice: '.($invoice->number ?? '-');
        $lines[] = 'Date: '.optional($invoice->issue_date ?? $invoice->created_at)->format('Y-m-d');
        $lines[] = 'Customer: '.($invoice->customer_name ?? '-');
        if ($invoice->customer_address) {
            $lines[] = 'Address: '.str_replace(["\r","\n"],' ', $invoice->customer_address);
        }
        if ($invoice->customer_tax_id) {
            $lines[] = 'Tax ID: '.$invoice->customer_tax_id;
        }
        if ($invoice->quotation_number) {
            $lines[] = 'From quotation: '.$invoice->quotation_number;
        }
        $lines[] = 'Items:';

        $subtotal = 0.0;
        if (method_exists($invoice, 'items')) {
            foreach ($invoice->items as $idx => $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $line  = ($qty * $price);
                $subtotal += $line;
                $title = ($idx+1).'. '.trim((string)($it->description ?? ''));
                $lines[] = $title ?: (($idx+1).'. —');
                $lines[] = sprintf('    qty %.2f x %.2f = %.2f', $qty, $price, $line);
            }
        }

        $taxRate = (float)($invoice->tax_rate ?? 0);
        $tax = (float)($invoice->tax ?? ($subtotal * ($taxRate/100)));
        $total = (float)($invoice->total ?? ($subtotal + $tax));

        $lines[] = 'Subtotal: '.number_format($subtotal, 2);
        $lines[] = 'Tax ('.number_format($taxRate,2).'%) : '.number_format($tax, 2);
        $lines[] = 'Total: '.number_format($total, 2);

        // Build the same minimal PDF structure as quotation
        $nl = "\r\n";
        $stream = "BT{$nl}/F1 12 Tf{$nl}50 780 Td{$nl}";
        foreach ($lines as $line) {
            $stream .= '('.self::escapeText($line).") Tj{$nl}0 -18 Td{$nl}";
        }
        $stream .= "ET{$nl}";

        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>';
        $objects[] = "<< /Length ".strlen($stream)." >>{$nl}stream{$nl}".$stream."endstream{$nl}";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pdf = "%PDF-1.4{$nl}%".chr(0xE2).chr(0xE3).chr(0xCF).chr(0xD3).$nl;
        $offsets = [];
        foreach ($objects as $i => $obj) {
            $offsets[$i] = strlen($pdf);
            $pdf .= ($i + 1).' 0 obj'.$nl.$obj."endobj{$nl}";
        }

        $pdf .= $nl;
        $xrefOffset = strlen($pdf);

        $pdf .= 'xref'.$nl;
        $pdf .= '0 '.(count($objects) + 1).$nl;
        $pdf .= '0000000000 65535 f '.$nl;
        foreach ($offsets as $off) {
            $pdf .= sprintf('%010d 00000 n '.$nl, $off);
        }

        $pdf .= 'trailer'.$nl;
        $pdf .= '<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'.$nl;
        $pdf .= 'startxref'.$nl.$xrefOffset.$nl;
        $pdf .= '%%EOF'.$nl;

        return $pdf;
    }

    private static function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\','\\(','\\)'], $text);
    }
}
