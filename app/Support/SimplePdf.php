<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

class SimplePdf
{
    /**
     * Generate a layout-aware PDF fallback (used when Dompdf isn't installed).
     */
    public static function quotation(Quotation $q): string
    {
        $theme = self::theme();

        $preparedBy = $q->salesperson
            ?? ($q->contact_name ?? ($q->created_by ?? null));
        $approvedBy = $q->approved_by ?? data_get($q, 'approver_name');
        $preparedOn = optional($q->created_at)->format('Y-m-d');
        $approvedOn = optional($q->approved_at ?? $q->updated_at)->format('Y-m-d');

        $lines = self::headerLines(
            title: 'Quotation',
            number: $q->number,
            date: optional($q->issue_date)->format('Y-m-d')
        );

        $customer = [
            'Customer' => $q->customer_name,
            'Address'  => $q->customer_address,
            'Tax ID'   => $q->customer_tax_id,
        ];

        [$items, $summary] = self::itemTable($q->items, $q->tax_rate, $q->tax, $q->total);

        $body = self::renderDocument(
            title: 'Quotation',
            headerText: $theme['header_text'],
            footerText: $theme['footer_text'],
            primary: $theme['primary'],
            watermark: $theme['watermark'],
            lines: $lines,
            customer: $customer,
            items: $items,
            summary: $summary,
            notes: $q->notes,
            layout: $theme['layout'],
            company: $theme['company'],
            preparedBy: $preparedBy,
            preparedOn: $preparedOn,
            approvedBy: $approvedBy,
            approvedOn: $approvedOn,
            logoDataUrl: $theme['logo']
        );

        return self::assemble($body);
    }

    public static function invoice(Invoice $invoice): string
    {
        $theme = self::theme();

        $preparedBy = $invoice->salesperson
            ?? ($invoice->contact_name ?? ($invoice->created_by ?? null));
        $approvedBy = $invoice->approved_by ?? data_get($invoice, 'approver_name');
        $preparedOn = optional($invoice->created_at)->format('Y-m-d');
        $approvedOn = optional($invoice->approved_at ?? $invoice->updated_at)->format('Y-m-d');

        $lines = self::headerLines(
            title: 'Invoice',
            number: $invoice->number,
            date: optional($invoice->issue_date ?? $invoice->created_at)->format('Y-m-d'),
            extra: $invoice->quotation_number ? 'From quotation: '.$invoice->quotation_number : null
        );

        $customer = [
            'Customer' => $invoice->customer_name,
            'Address'  => $invoice->customer_address,
            'Tax ID'   => $invoice->customer_tax_id,
        ];

        [$items, $summary] = self::itemTable($invoice->items, $invoice->tax_rate, $invoice->tax, $invoice->total);

        $body = self::renderDocument(
            title: 'Invoice',
            headerText: $theme['header_text'],
            footerText: $theme['footer_text'],
            primary: $theme['primary'],
            watermark: $theme['watermark'],
            lines: $lines,
            customer: $customer,
            items: $items,
            summary: $summary,
            notes: $invoice->notes,
            layout: $theme['layout'],
            company: $theme['company'],
            preparedBy: $preparedBy,
            preparedOn: $preparedOn,
            approvedBy: $approvedBy,
            approvedOn: $approvedOn,
            logoDataUrl: $theme['logo']
        );

        return self::assemble($body);
    }

    protected static function theme(): array
    {
        $settings = [];
        try {
            $settings = Setting::allCached();
        } catch (\Throwable $e) {
            // ignore
        }

        $layout = json_decode($settings['pdf_layout'] ?? '[]', true) ?: [];

        $logoPath = $settings['logo_path'] ?? null;
        $logoDataUrl = $settings['logo_data_url'] ?? ($settings['logo'] ?? null);
        if (!$logoDataUrl && $logoPath && Storage::disk('public')->exists($logoPath)) {
            $mime = Storage::disk('public')->mimeType($logoPath) ?: 'image/png';
            $logoDataUrl = 'data:'.$mime.';base64,'.base64_encode(Storage::disk('public')->get($logoPath));
        }

        return [
            'primary' => $settings['primary_color'] ?? '#31689E',
            'header_text' => $settings['header_text'] ?? ' ',
            'footer_text' => $settings['footer_text'] ?? ' ',
            'watermark' => $layout['watermark_text'] ?? '',
            'logo' => $logoDataUrl,
            'logo_path' => $logoPath,
            'company' => [
                'name' => $settings['company_name'] ?? '',
                'address' => $settings['company_address'] ?? '',
                'phone' => $settings['company_phone'] ?? '',
                'tax_id' => $settings['company_tax_id'] ?? '',
            ],
            'layout' => [
                'show_logo' => (bool) ($layout['show_logo'] ?? true),
                'margin_top' => $layout['margin_top'] ?? 30,
                'margin_bottom' => $layout['margin_bottom'] ?? 26,
            ],
        ];
    }

    protected static function headerLines(string $title, ?string $number, ?string $date, ?string $extra = null): array
    {
        $lines = [];
        $lines[] = $title.': '.($number ?: '-');
        $lines[] = 'Date: '.($date ?: '-');
        if ($extra) {
            $lines[] = $extra;
        }

        return $lines;
    }

    protected static function itemTable($items, $taxRateRaw, $taxRaw, $totalRaw): array
    {
        $rows = [];
        $subtotal = 0.0;

        if ($items) {
            foreach ($items as $idx => $it) {
                $qty   = (float)($it->qty ?? $it->quantity ?? 0);
                $price = (float)($it->price ?? $it->unit_price ?? 0);
                $disc  = (float)($it->discount ?? 0);
                $line  = max(($qty * $price) - $disc, 0);
                $subtotal += $line;

                $rows[] = [
                    'idx' => $idx + 1,
                    'desc' => (string)($it->description ?? '—'),
                    'qty' => number_format($qty, 2),
                    'price' => number_format($price, 2),
                    'line' => number_format($line, 2),
                ];
            }
        }

        $taxRate = (float)($taxRateRaw ?? 0);
        $tax = (float)($taxRaw ?? ($subtotal * ($taxRate/100)));
        $total = (float)($totalRaw ?? ($subtotal + $tax));

        $summary = [
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax, 2),
            'tax_rate' => number_format($taxRate, 2),
            'total' => number_format($total, 2),
        ];

        return [$rows, $summary];
    }

    protected static function renderDocument(string $title, string $headerText, string $footerText, string $primary, string $watermark, array $lines, array $customer, array $items, array $summary, ?string $notes, array $layout, array $company = [], ?string $preparedBy = null, ?string $preparedOn = null, ?string $approvedBy = null, ?string $approvedOn = null, ?string $logoDataUrl = null): string
    {
        $canvas = new SimplePdfCanvas();
        $canvas->setMargins(36, 36, max(26, (int)($layout['margin_bottom'] ?? 26)));

        $primaryRgb = self::rgb($primary);
        $muted = [0.16, 0.2, 0.28];

        // Top header bar with breathing room
        $headerTop = 792;
        $headerHeight = 96;
        $canvas->fillRect(36, $headerTop - $headerHeight, 523, $headerHeight, '#f7faff');
        $canvas->line(36, $headerTop - $headerHeight + 14, 559, $headerTop - $headerHeight + 14, $primary);

        // Logo + company block
        if (($layout['show_logo'] ?? true) && $logoDataUrl) {
            $canvas->imageDataUrl($logoDataUrl, 48, $headerTop - 80, 150, 70);
        }

        $companyLines = array_values(array_filter([
            $company['name'] ?? null,
            $company['address'] ?? null,
            !empty($company['phone']) ? 'Phone: '.$company['phone'] : null,
            !empty($company['tax_id']) ? 'Tax ID: '.$company['tax_id'] : null,
        ]));

        $cy = $headerTop - 16;
        foreach ($companyLines as $cLine) {
            $canvas->text($cLine, 210, $cy, 10, 'F2', $muted);
            $cy -= 12;
        }

        // Title box on the right
        $canvas->fillRect(388, $headerTop - 74, 171, 62, '#e7effa');
        $canvas->line(388, $headerTop - 50, 559, $headerTop - 50, $primary);
        $canvas->text(strtoupper($title), 396, $headerTop - 38, 16, 'F2', $primaryRgb);
        $canvas->text($headerText ?: ' ', 396, $headerTop - 56, 9, 'F1', $muted);

        // Document meta box beneath title
        $metaTop = $headerTop - 88;
        $metaHeight = 60 + (count($lines) * 13);
        $metaBottom = $metaTop - $metaHeight;
        $canvas->fillRect(36, $metaBottom, 300, $metaHeight, '#eef3fb');
        $canvas->line(36, $metaTop, 336, $metaTop, $primary);
        $canvas->text($title.' Info', 44, $metaTop - 6, 11, 'F2', $primaryRgb);
        $ty = $metaTop - 18;
        foreach ($lines as $line) {
            $canvas->text($line, 44, $ty, 10, 'F1', $muted);
            $ty -= 13;
        }

        // Customer & office panel
        $panelTop = $metaBottom - 10;
        $customerLines = array_filter($customer);
        $panelHeight = max(count($customerLines), count($companyLines)) * 13 + 34;
        $panelHeight = max($panelHeight, 90);
        $canvas->fillRect(36, $panelTop - $panelHeight, 523, $panelHeight, '#f9fbfd');
        $canvas->line(36, $panelTop, 559, $panelTop, $primary);

        $canvas->text('Customer', 44, $panelTop - 6, 11, 'F2', $primaryRgb);
        $ty = $panelTop - 20;
        foreach ($customer as $label => $value) {
            if ($value) {
                $canvas->text($label.': '.$value, 44, $ty, 10, 'F1', $muted);
                $ty -= 12;
            }
        }

        if ($companyLines) {
            $canvas->text('Office', 332, $panelTop - 6, 11, 'F2', $primaryRgb);
            $oy = $panelTop - 20;
            foreach ($companyLines as $cLine) {
                $canvas->text($cLine, 332, $oy, 10, 'F1', $muted);
                $oy -= 12;
            }
        }

        $y = $panelTop - $panelHeight - 24;

        // Items table
        $canvas->tableHeader($y, $primary);
        $y -= 18;
        foreach ($items as $row) {
            $canvas->tableRow($y, $row);
            $y -= 18;
        }

        $y -= 8;
        $canvas->line(36, $y, 559, $y, '#d9e2f3');
        $y -= 12;

        // Totals + remark block
        $canvas->fillRect(304, $y - 126, 255, 140, '#eef3fb');
        $canvas->line(304, $y + 10, 559, $y + 10, $primary);
        $canvas->text('Totals', 312, $y + 4, 11, 'F2', $primaryRgb);

        $ty = $y - 16;
        $canvas->text('Subtotal', 312, $ty, 10);
        $canvas->text($summary['subtotal'], 542, $ty, 10);
        $ty -= 18;
        $canvas->text('Tax ('.$summary['tax_rate'].'%)', 312, $ty, 10);
        $canvas->text($summary['tax'], 542, $ty, 10);
        $ty -= 20;
        $canvas->text('Total', 312, $ty, 13, 'F2', $primaryRgb);
        $canvas->text($summary['total'], 542, $ty, 13, 'F2', [0,0,0]);

        if ($notes) {
            $canvas->fillRect(36, $y - 64, 258, 78, '#f6f9fc');
            $canvas->text('Remark', 44, $y + 2, 11, 'F2', $primaryRgb);
            $canvas->text($notes, 44, $y - 14, 10, 'F1', $muted);
        }

        $y = $y - 136;

        // Sign-off
        if ($preparedBy || $approvedBy) {
            $canvas->line(36, $y + 4, 559, $y + 4, '#d9e2f3');
            $canvas->text('Created by: '.($preparedBy ?: '-'), 44, $y - 8, 10);
            if ($preparedOn) {
                $canvas->text('Date: '.$preparedOn, 220, $y - 8, 10);
            }
            $canvas->text('Approved by: '.($approvedBy ?: '-'), 360, $y - 8, 10);
            if ($approvedOn) {
                $canvas->text('Date: '.$approvedOn, 520, $y - 8, 10);
            }
            $y -= 18;
        }

        if ($watermark) {
            $canvas->watermark($watermark, '#dbe4f0');
        }

        if ($footerText) {
            $canvas->text($footerText, 40, 40, 9, 'F1', [0.4,0.45,0.5]);
        }

        return $canvas->content();
    }

    protected static function assemble(string $stream): string
    {
        $nl = "\r\n";
        $objects = [];
        $objects[] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[] = '<< /Type /Pages /Kids [3 0 R] /Count 1 >>';
        $objects[] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R /F2 6 0 R >> >> >>';
        $objects[] = "<< /Length ".strlen($stream)." >>{$nl}stream{$nl}".$stream."endstream{$nl}";
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

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

    public static function rgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0.19, 0.41, 0.62];
        }
        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255,
        ];
    }

    public static function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\','\\(','\\)'], $text);
    }
}

class SimplePdfCanvas
{
    protected string $stream = '';
    protected int $marginLeft = 36;
    protected int $marginBottom = 36;

    public function setMargins(int $left, int $right, int $bottom): void
    {
        $this->marginLeft = $left;
        $this->marginBottom = $bottom;
    }

    public function text(string $text, float $x, float $y, int $size = 11, string $font = 'F1', array $color = [0,0,0]): void
    {
        $this->stream .= sprintf("BT\n%.3f %.3f %.3f rg\n/%s %d Tf\n%.2f %.2f Td\n(%s) Tj\nET\n",
            $color[0], $color[1], $color[2], $font, $size, $x, $y, SimplePdf::escapeText($text)
        );
    }

    public function line(float $x1, float $y1, float $x2, float $y2, string $color = '#dbe4f0'): void
    {
        [$r,$g,$b] = SimplePdf::rgb($color);
        $this->stream .= sprintf("%.3f %.3f %.3f RG\n%.3f %.3f m\n%.3f %.3f l\nS\n", $r,$g,$b,$x1,$y1,$x2,$y2);
    }

    public function fillRect(float $x, float $y, float $w, float $h, string $color): void
    {
        [$r,$g,$b] = SimplePdf::rgb($color);
        $this->stream .= sprintf("%.3f %.3f %.3f rg\n%.2f %.2f %.2f %.2f re f\n", $r,$g,$b,$x,$y,$w,$h);
    }

    public function tableHeader(float $y, string $primary): void
    {
        $this->fillRect(36, $y-2, 523, 16, $primary);
        $this->text('#', 44, $y+3, 10, 'F2', [1,1,1]);
        $this->text('Description', 70, $y+3, 10, 'F2', [1,1,1]);
        $this->text('Qty', 370, $y+3, 10, 'F2', [1,1,1]);
        $this->text('Unit', 430, $y+3, 10, 'F2', [1,1,1]);
        $this->text('Total', 500, $y+3, 10, 'F2', [1,1,1]);
    }

    public function tableRow(float $y, array $row): void
    {
        $this->text((string) $row['idx'], 44, $y, 10);
        $this->text($row['desc'], 70, $y, 10);
        $this->text($row['qty'], 370, $y, 10);
        $this->text($row['price'], 430, $y, 10);
        $this->text($row['line'], 500, $y, 10);
    }

    public function watermark(string $text, string $primary): void
    {
        [$r,$g,$b] = SimplePdf::rgb($primary);
        $this->stream .= sprintf("q %.3f %.3f %.3f rg 0.25 w\n1 0 0.2 1 180 360 cm\nBT\n/F2 36 Tf\n50 300 Td\n(%s) Tj\nET\nQ\n",
            $r,$g,$b, SimplePdf::escapeText($text)
        );
    }

    public function content(): string
    {
        return $this->stream;
    }

    public function imageDataUrl(string $dataUrl, float $x, float $y, float $maxWidth = 100, float $maxHeight = 40): void
    {
        if (!str_starts_with($dataUrl, 'data:')) {
            return;
        }

        $parts = explode(',', $dataUrl, 2);
        if (count($parts) !== 2) {
            return;
        }

        $binary = base64_decode($parts[1], true);
        if ($binary === false) {
            return;
        }

        $image = @imagecreatefromstring($binary);
        if (!$image) {
            return;
        }

        $origWidth = imagesx($image);
        $origHeight = imagesy($image);

        $scale = min($maxWidth / max($origWidth, 1), $maxHeight / max($origHeight, 1), 1.0);
        $width = max(1, $origWidth * $scale);
        $height = max(1, $origHeight * $scale);

        ob_start();
        imagejpeg($image, null, 90);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        if (!$jpeg) {
            return;
        }

        $this->stream .= sprintf("q %.2f 0 0 %.2f %.2f %.2f cm\n", $width, $height, $x, $y);
        $this->stream .= sprintf("BI /W %d /H %d /CS /RGB /BPC 8 /F [/DCTDecode] ID\n", (int) $width, (int) $height);
        $this->stream .= $jpeg."\nEI\nQ\n";
    }
}
