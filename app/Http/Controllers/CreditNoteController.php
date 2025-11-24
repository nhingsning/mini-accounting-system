<?php

namespace App\Http\Controllers;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CreditNoteController extends Controller
{
    public function index()
    {
        $q = request('q');
        $type = request('type');

        $notes = CreditNote::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('number', 'like', "%{$q}%")
                        ->orWhere('customer_name', 'like', "%{$q}%")
                        ->orWhere('invoice_number', 'like', "%{$q}%");
                });
            })
            ->when($type, fn ($qq) => $qq->where('type', $type))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(15);

        return view('credit-notes.index', compact('notes', 'q', 'type'));
    }

    public function create()
    {
        $invoice = null;
        if (request('invoice')) {
            $invoice = $this->findInvoice(request('invoice'));
            $invoice?->loadMissing(['items' => fn ($q) => $q->orderBy('id')]);
        }

        return view('credit-notes.create', [
            'invoice' => $invoice,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $items = $this->validatedItems($request);

        $invoice = $this->findInvoice($data['invoice_id'] ?? null);
        if ($invoice && empty($data['invoice_number'])) {
            $data['invoice_number'] = $invoice->number;
        }

        if (empty($data['number'])) {
            $data['number'] = $this->nextNumber($data['type'] ?? 'credit');
        }

        $totals = $this->totalsFromItems($items);
        $data['subtotal'] = $data['subtotal'] ?? $totals['subtotal'];
        $data['tax'] = $data['tax'] ?? $totals['tax'];
        $data['total'] = $data['total'] ?? $totals['total'];

        $data = $this->filterPersistableColumns($data);

        $note = DB::transaction(function () use ($data, $items) {
            $note = CreditNote::create($data);

            foreach ($items as $item) {
                $note->items()->create($item);
            }

            return $note;
        });

        $slug = $note->number ?: $note->id;
        return redirect()->route('credit-notes.show', $slug)->with('ok', 'บันทึกใบลดหนี้/เพิ่มหนี้แล้ว');
    }

    public function show(string $key)
    {
        $note = $this->findNote($key);
        $note->loadMissing(['invoice', 'items' => fn ($q) => $q->orderBy('id')]);

        return view('credit-notes.show', ['note' => $note]);
    }

    public function edit(string $key)
    {
        $note = $this->findNote($key);
        $note->loadMissing(['invoice', 'items' => fn ($q) => $q->orderBy('id')]);

        return view('credit-notes.edit', [
            'note' => $note,
            'invoice' => $note->invoice,
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function update(Request $request, string $key)
    {
        $note = $this->findNote($key);

        $data = $this->validateData($request, $note->id);
        $items = $this->validatedItems($request);

        $invoice = $this->findInvoice($data['invoice_id'] ?? null);
        if ($invoice && empty($data['invoice_number'])) {
            $data['invoice_number'] = $invoice->number;
        }

        if (empty($data['number'])) {
            $data['number'] = $this->nextNumber($data['type'] ?? $note->type);
        }

        $totals = $this->totalsFromItems($items);
        $data['subtotal'] = $data['subtotal'] ?? $totals['subtotal'];
        $data['tax'] = $data['tax'] ?? $totals['tax'];
        $data['total'] = $data['total'] ?? $totals['total'];

        $data = $this->filterPersistableColumns($data);

        DB::transaction(function () use ($note, $data, $items) {
            $note->update($data);
            $note->items()->delete();
            foreach ($items as $item) {
                $note->items()->create($item);
            }
        });

        $slug = $note->number ?: $note->id;
        return redirect()->route('credit-notes.show', $slug)->with('ok', 'อัปเดตแล้ว');
    }

    public function destroy(string $key)
    {
        $note = $this->findNote($key);
        $note->delete();

        return redirect()->route('credit-notes.index')->with('ok', 'ลบข้อมูลแล้ว');
    }

    public function convertFromInvoice(Request $request, string $invoiceKey, string $type)
    {
        $type = $this->normalizeType($type);
        $invoice = $this->findInvoice($invoiceKey);
        abort_unless($invoice, 404, 'Invoice not found');
        $invoice->loadMissing(['items' => fn ($q) => $q->orderBy('id')]);

        $data = [
            'type'            => $type,
            'invoice_id'      => $invoice->id,
            'invoice_number'  => $invoice->number,
            'customer_name'   => $invoice->customer_name,
            'customer_address'=> $invoice->customer_address,
            'customer_tax_id' => $invoice->customer_tax_id,
            'customer_branch_type' => $invoice->customer_branch_type,
            'customer_branch_code' => $invoice->customer_branch_code,
            'issue_date'      => now()->toDateString(),
            'currency'        => $invoice->currency ?? 'THB',
            'status'          => 'draft',
        ];

        $items = $invoice->items?->map(function ($it) use ($type) {
            $qty = $it->qty ?? $it->quantity ?? 1;
            $price = $it->unit_price ?? $it->price ?? 0;
            $line = $it->line_total ?? ($qty * $price);
            // for credit notes return stock, for debit notes use positive adjustment
            $lineSigned = $type === 'credit' ? -1 * abs($line) : abs($line);
            return [
                'description' => $it->description,
                'qty'         => $qty,
                'unit_price'  => $price,
                'line_total'  => $lineSigned,
                'unit'        => $it->unit ?? null,
            ];
        })->toArray() ?? [];

        $totals = $this->totalsFromItems($items);
        $data['subtotal'] = $totals['subtotal'];
        $data['tax'] = $totals['tax'];
        $data['total'] = $totals['total'];
        $data['number'] = $this->nextNumber($type);

        $note = DB::transaction(function () use ($data, $items) {
            $note = CreditNote::create($data);
            foreach ($items as $item) {
                $note->items()->create($item);
            }
            return $note;
        });

        $slug = $note->number ?: $note->id;
        return redirect()->route('credit-notes.show', $slug)->with('ok', 'สร้างใบ'.($type === 'credit' ? 'ลดหนี้' : 'เพิ่มหนี้').'จากใบแจ้งหนี้แล้ว');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'number'               => ['nullable','string','max:255', $this->numberRule($ignoreId)],
            'invoice_id'           => 'nullable|integer|exists:invoices,id',
            'invoice_number'       => 'nullable|string|max:255',
            'type'                 => ['required','string', Rule::in(['credit','debit'])],
            'status'               => ['nullable','string','max:50', Rule::in(array_keys($this->statusOptions()))],
            'issue_date'           => 'nullable|date',
            'customer_name'        => 'nullable|string|max:255',
            'customer_address'     => 'nullable|string',
            'customer_tax_id'      => 'nullable|string|max:50',
            'customer_branch_type' => 'nullable|string|max:20',
            'customer_branch_code' => 'nullable|string|max:20',
            'reason'               => 'nullable|string',
            'subtotal'             => 'nullable|numeric',
            'tax'                  => 'nullable|numeric',
            'total'                => 'nullable|numeric',
            'currency'             => 'nullable|string|max:10',
        ]);
    }

    private function validatedItems(Request $request): array
    {
        $items = $request->input('items', []);
        if (!is_array($items)) {
            return [];
        }
        return collect($items)->map(function ($item) {
            $qty = (float) ($item['qty'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $line = $item['line_total'] ?? ($qty * $price);
            return [
                'description' => $item['description'] ?? '',
                'qty'         => $qty,
                'unit_price'  => $price,
                'line_total'  => (float) $line,
                'unit'        => $item['unit'] ?? null,
            ];
        })->toArray();
    }

    private function totalsFromItems(array $items): array
    {
        $subtotal = collect($items)->sum(fn ($it) => $it['line_total'] ?? 0);
        $tax = 0;
        $total = $subtotal + $tax;
        return compact('subtotal', 'tax', 'total');
    }

    private function numberRule(?int $ignoreId = null)
    {
        $rule = Rule::unique('credit_notes', 'number');
        if ($ignoreId) {
            $rule = $rule->ignore($ignoreId);
        }
        return $rule;
    }

    private function nextNumber(string $type): string
    {
        $type = $this->normalizeType($type);
        $prefix = $type === 'credit' ? 'CN' : 'DN';
        $period = now()->format('Y-m');
        $base = $prefix.$period.'-';
        $latest = CreditNote::where('type', $type)
            ->where('number', 'like', $base.'%')
            ->orderByDesc('number')
            ->value('number');

        $next = 1;
        if ($latest && preg_match('/-(\d+)$/', $latest, $m)) {
            $next = (int) $m[1] + 1;
        }

        return sprintf('%s%04d', $base, $next);
    }

    private function statusOptions(): array
    {
        return [
            'draft'     => 'Draft',
            'issued'    => 'Issued',
            'cancelled' => 'Cancelled / Void',
        ];
    }

    private function normalizeType(string $type): string
    {
        return $type === 'debit' ? 'debit' : 'credit';
    }

    private function findNote(string $key): CreditNote
    {
        $note = ctype_digit($key)
            ? CreditNote::whereKey($key)->first()
            : CreditNote::where('number', $key)->first();

        abort_unless($note, 404, 'Credit/Debit Note not found');
        return $note;
    }

    private function findInvoice($key): ?Invoice
    {
        if (!$key) {
            return null;
        }

        return Invoice::query()
            ->onlyInvoices()
            ->where(function ($q) use ($key) {
                $q->where('id', $key)->orWhere('number', $key);
            })
            ->first();
    }

    private function filterPersistableColumns(array $data): array
    {
        if (!Schema::hasTable('credit_notes')) {
            return $data;
        }
        $columns = Schema::getColumnListing('credit_notes');

        return collect($data)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->toArray();
    }
}
