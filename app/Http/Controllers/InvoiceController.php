<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Support\SimplePdf;
use App\Support\PeriodLock;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class InvoiceController extends Controller
{
    // ===== List + Search =====
    public function index()
    {
        $q = request('q');

        $invoices = Invoice::query()
            ->onlyInvoices()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('number','like',"%{$q}%")
                      ->orWhere('customer_name','like',"%{$q}%");
                });
            })
            ->select('id','number','customer_name','issue_date','status','total','created_at')
            ->orderByDesc('issue_date')
            ->paginate(15);

        return view('invoices.index', [
            'invoices' => $invoices,
            'q' => $q,
        ]);
    }

    // ===== Helpers =====
    private function findByKey(string $key): Invoice
    {
        $invoiceQuery = Invoice::query()->onlyInvoices()->withTrashed();

        $invoice = ctype_digit($key)
            ? $invoiceQuery->whereKey($key)->first()
            : $invoiceQuery->where('number',$key)->first();

        abort_unless($invoice, 404, 'Invoice not found');
        return $invoice;
    }

    // ===== Create form (HTML view) =====
    public function create()
    {
        return view('invoices.create', [
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    // ===== Store action =====
    public function store(Request $request)
    {
        $data = $request->validate([
            'number'               => ['nullable','string','max:255', $this->invoiceNumberRule()],
            'quotation_number'     => 'nullable|string|max:255',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'customer_name'        => 'required|string|max:255',
            'customer_address'     => 'nullable|string',
            'customer_tax_id'      => 'nullable|string|max:50',
            'customer_branch_type' => 'nullable|string|max:20',
            'customer_branch_code' => 'nullable|string|max:20',
            'issue_date'           => 'nullable|date',
            'due_date'             => 'nullable|date',
            'discount_percent'     => 'nullable|numeric',
            'vat_enabled'          => 'sometimes|boolean',
            'tax_rate'             => 'nullable|numeric',
            'subtotal'             => 'nullable|numeric',
            'tax'                  => 'nullable|numeric',
            'total'                => 'nullable|numeric',
            'status'               => ['nullable','string','max:50', Rule::in($this->allowedStatusValues())],
            'currency'             => 'nullable|string|max:10',
        ]);

        $data['vat_enabled'] = $request->boolean('vat_enabled');
        $data['status'] = $this->normalizeStatus($data['status'] ?? '');

        PeriodLock::assertOpen($data['issue_date'] ?? now(), 'invoice');
        $data = $this->filterPersistableColumns($data);

        $invoice = DB::transaction(function () use ($data, $request) {
            $invoice = Invoice::create($data);
            AuditLogger::record($invoice, $request->user(), 'created', [
                'status' => $invoice->status,
                'total'  => $invoice->total,
            ]);

            // Seed a drafter step as completed and prepare the next approver step
            if (Schema::hasTable('document_approvals')) {
                $invoice->approvals()->create([
                    'step'   => 1,
                    'role'   => 'drafter',
                    'status' => 'approved',
                    'acted_at' => now(),
                    'user_id'  => $request->user()?->getAuthIdentifier(),
                ]);
                $invoice->approvals()->create([
                    'step'   => 2,
                    'role'   => 'approver',
                    'status' => 'pending',
                ]);

                $invoice->forceFill([
                    'approval_status' => 'in_review',
                    'approval_step'   => 2,
                ])->saveQuietly();
            }

            return $invoice;
        });

        $slug = $invoice->number ?: $invoice->id;

        return redirect()
            ->route('invoices.show', $slug)
            ->with('ok', 'Created');
    }

    // ===== Show (HTML view) =====
    public function show(string $key)
    {
        $invoice = $this->findByKey($key);
        $receiptsAvailable = Schema::hasTable('receipts');
        $paymentsAvailable = Schema::hasTable('payments');
        $approvalsAvailable = Schema::hasTable('document_approvals');
        $auditAvailable = Schema::hasTable('audit_logs');

        $relations = [
            'items' => fn ($q) => $q->orderBy('id'),
            'quotation',
        ];
        if ($approvalsAvailable) {
            $relations[] = 'approvals.user';
        }
        if ($auditAvailable) {
            $relations[] = 'auditLogs.user';
        }
        if ($receiptsAvailable) {
            $relations[] = 'receipt';
        }
        if ($paymentsAvailable) {
            $relations[] = 'payments';
        }

        $invoice->loadMissing($relations);
        if (!$receiptsAvailable) {
            $invoice->setRelation('receipt', null);
        }
        if (!$paymentsAvailable) {
            $invoice->setRelation('payments', collect());
        } else {
            $invoice->recalculatePaymentTotals();
        }
        if (!$approvalsAvailable) {
            $invoice->setRelation('approvals', collect());
        }
        if (!$auditAvailable) {
            $invoice->setRelation('auditLogs', collect());
        }

        return view('invoices.show', compact('invoice', 'receiptsAvailable'));
    }

    public function pdf(string $key)
    {
        $invoice = $this->findByKey($key);
        $invoice->loadMissing([
            'items' => fn ($q) => $q->orderBy('id'),
            'quotation',
        ]);

        if (class_exists(Dompdf::class)) {
            $html = view('invoices.pdf', ['invoice' => $invoice])->render();

            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('isHtml5ParserEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4');
            $dompdf->render();

            $payload = $dompdf->output();
        } else {
            $payload = SimplePdf::invoice($invoice);
        }

        $filename = ($invoice->number ?? 'invoice').'.pdf';
        $dir = storage_path('app/invoices');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = $dir.'/'.$filename;
        file_put_contents($path, $payload);

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    // ===== Edit form (HTML view) =====
    public function edit(string $key)
    {
        $invoice = $this->findByKey($key);
        return view('invoices.edit', [
            'invoice'        => $invoice,
            'statusOptions'  => $this->statusOptions(),
        ]);
    }

    // ===== Update action =====
    public function update(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);
        PeriodLock::assertOpen($request->input('issue_date', $invoice->issue_date), 'invoice');
        $before = $invoice->only(['status', 'total', 'approval_status', 'approval_step']);

        $data = $request->validate([
            'number'               => ['nullable','string','max:255', $this->invoiceNumberRule($invoice->id)],
            'quotation_number'     => 'nullable|string|max:255',
            'customer_id'          => 'nullable|integer|exists:customers,id',
            'customer_name'        => 'required|string|max:255',
            'customer_address'     => 'nullable|string',
            'customer_tax_id'      => 'nullable|string|max:50',
            'customer_branch_type' => 'nullable|string|max:20',
            'customer_branch_code' => 'nullable|string|max:20',
            'issue_date'           => 'nullable|date',
            'due_date'             => 'nullable|date',
            'discount_percent'     => 'nullable|numeric',
            'vat_enabled'          => 'sometimes|boolean',
            'tax_rate'             => 'nullable|numeric',
            'subtotal'             => 'nullable|numeric',
            'tax'                  => 'nullable|numeric',
            'total'                => 'nullable|numeric',
            'status'               => ['required','string','max:50', Rule::in($this->allowedStatusValues())],
        ]);

        $data['vat_enabled'] = $request->boolean('vat_enabled');
        $data['status'] = $this->normalizeStatus($data['status'] ?? $invoice->status);
        $data['total'] = $data['total'] ?? $invoice->total;

        $data = $this->filterPersistableColumns($data);

        $invoice->update($data);

        $invoice->refresh();
        $tracked = ['status', 'total', 'approval_status', 'approval_step'];
        $changes = [];
        foreach ($tracked as $field) {
            if (($before[$field] ?? null) !== $invoice->{$field}) {
                $changes[$field] = [
                    'from' => $before[$field] ?? null,
                    'to'   => $invoice->{$field},
                ];
            }
        }

        if ($changes) {
            AuditLogger::record($invoice, $request->user(), 'updated', $changes);
        }

        $slug = $invoice->number ?: $invoice->id;
        return redirect()->route('invoices.show', $slug)->with('ok','Updated');
    }

    // ===== Delete action =====
    public function destroy(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);
        PeriodLock::assertOpen($invoice->issue_date, 'invoice');

        $reason = $request->input('reason') ?: __('ui.default_cancel_reason');

        if (Schema::hasColumn('invoices', 'status_before_cancellation') && $invoice->status !== 'cancelled') {
            $invoice->status_before_cancellation = $invoice->status;
        }

        $invoice->forceFill([
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => now(),
        ])->saveQuietly();

        $invoice->delete();

        return redirect()->route('invoices.index')->with('ok', 'Deleted');
    }

    public function restore(string $key)
    {
        $invoiceQuery = Invoice::query()->onlyInvoices()->withTrashed();
        $invoice = ctype_digit($key)
            ? $invoiceQuery->whereKey($key)->first()
            : $invoiceQuery->where('number', $key)->first();

        abort_unless($invoice, 404, 'Invoice not found');
        PeriodLock::assertOpen($invoice->issue_date, 'invoice');

        $invoice->restore();

        if ($invoice->status_before_cancellation) {
            $invoice->forceFill(['status' => $invoice->status_before_cancellation]);
        }

        $invoice->forceFill(['cancelled_at' => null])->saveQuietly();

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', __('ui.restored'));
    }

    public function submitForApproval(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);

        DB::transaction(function () use ($invoice, $request) {
            if (!$invoice->approvals()->where('status', 'pending')->exists()) {
                $invoice->approvals()->create([
                    'step'   => max(1, (int) $invoice->approval_step + 1),
                    'role'   => 'approver',
                    'status' => 'pending',
                ]);
            }

            $invoice->forceFill([
                'approval_status' => 'in_review',
                'approval_step'   => $invoice->approvals()->where('status', 'pending')->min('step') ?? 1,
            ])->saveQuietly();

            AuditLogger::record($invoice, $request->user(), 'submitted_for_approval', [
                'step' => $invoice->approval_step,
            ]);
        });

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', 'Sent for approval');
    }

    public function approve(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);
        $user = Auth::user();
        $this->ensureApproverRole($user);

        $approval = $invoice->approvals()->where('status', 'pending')->orderBy('step')->first();
        if (!$approval) {
            return redirect()->back()->with('ok', 'No pending approval');
        }

        DB::transaction(function () use ($approval, $invoice, $request, $user) {
            $approval->update([
                'status'   => 'approved',
                'comment'  => $request->input('comment'),
                'acted_at' => now(),
                'user_id'  => $user?->getAuthIdentifier(),
            ]);

            $invoice->forceFill([
                'approval_status' => 'approved',
                'approval_step'   => $approval->step,
            ])->saveQuietly();

            AuditLogger::record($invoice, $user, 'approved', [
                'step'    => $approval->step,
                'comment' => $request->input('comment'),
            ]);
        });

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', 'Approved');
    }

    public function reject(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);
        $user = Auth::user();
        $this->ensureApproverRole($user);

        $approval = $invoice->approvals()->where('status', 'pending')->orderBy('step')->first();
        if (!$approval) {
            return redirect()->back()->with('ok', 'No pending approval');
        }

        DB::transaction(function () use ($approval, $invoice, $request, $user) {
            $approval->update([
                'status'   => 'rejected',
                'comment'  => $request->input('comment'),
                'acted_at' => now(),
                'user_id'  => $user?->getAuthIdentifier(),
            ]);

            $invoice->forceFill([
                'approval_status' => 'rejected',
                'approval_step'   => $approval->step,
            ])->saveQuietly();

            AuditLogger::record($invoice, $user, 'rejected', [
                'step'    => $approval->step,
                'comment' => $request->input('comment'),
            ]);
        });

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', 'Rejected');
    }

    private function statusOptions(): array
    {
        return [
            'pending'   => 'Pending / Waiting for Approval',
            'approved'  => 'Approved',
            'partial'   => 'Partially Paid',
            'paid'      => 'Paid',
            'cancelled' => 'Cancelled / Void',
        ];
    }

    private function invoiceNumberRule(?int $ignoreId = null)
    {
        $rule = Rule::unique('invoices', 'number');

        if (Schema::hasColumn('invoices', 'status')) {
            $rule = $rule->where(fn ($q) => $q->whereNotIn('status', ['cancelled', 'void']));
        }

        if ($ignoreId) {
            $rule = $rule->ignore($ignoreId);
        }

        return $rule;
    }

    private function allowedStatusValues(): array
    {
        return array_merge(array_keys($this->statusOptions()), [
            'draft', 'sent', 'unpaid', 'void', 'cancel', 'waiting', 'waiting for approval', 'waiting_for_approval',
        ]);
    }

    private function ensureApproverRole($user): void
    {
        $role = strtolower((string) ($user->role ?? ''));
        $allowed = ['approver', 'admin', 'manager'];
        abort_unless(in_array($role, $allowed, true), 403, 'Approval permission required');
    }

    private function normalizeStatus(?string $status): string
    {
        $normalized = strtolower(trim((string) $status));
        $normalized = match ($normalized) {
            'draft', 'sent', 'unpaid', 'waiting', 'waiting for approval', 'waiting_for_approval' => 'pending',
            'void', 'cancel' => 'cancelled',
            default => $normalized,
        };

        return in_array($normalized, array_keys($this->statusOptions()), true)
            ? $normalized
            : 'pending';
    }

    private function filterPersistableColumns(array $data): array
    {
        if (!Schema::hasTable('invoices')) {
            return $data;
        }

        $columns = Schema::getColumnListing('invoices');

        return collect($data)
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->toArray();
    }
}
