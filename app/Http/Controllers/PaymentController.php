<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function index()
    {
        $this->ensurePaymentsTable();

        $payments = Payment::with('invoice')
            ->latest('paid_at')
            ->latest()
            ->take(50)
            ->get();

        $invoices = Invoice::orderByDesc('issue_date')
            ->orderByDesc('id')
            ->take(50)
            ->get();

        return view('payments.index', compact('payments', 'invoices'));
    }

    public function store(Request $request)
    {
        $this->ensurePaymentsTable();

        $data = $request->validate([
            'invoice_id'   => ['required', 'integer', 'exists:invoices,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'currency'     => ['nullable', 'string', 'max:10'],
            'method'       => ['required', Rule::in(['bank_transfer', 'cash', 'card', 'e_wallet'])],
            'reference'    => ['nullable', 'string', 'max:255'],
            'note'         => ['nullable', 'string'],
            'paid_at'      => ['nullable', 'date'],
            'status'       => ['nullable', Rule::in(['pending', 'cleared', 'reconciled', 'void'])],
            'slip'         => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ]);

        $invoice = Invoice::findOrFail($data['invoice_id']);
        $data['currency'] = $data['currency'] ?? $invoice->currency ?? config('app.currency', 'THB');
        $data['status'] = $data['status'] ?? 'cleared';
        $data['paid_at'] = $data['paid_at'] ?? now();

        if ($request->hasFile('slip')) {
            $data['slip_path'] = $request->file('slip')->store('payment-slips', 'public');
        }

        $invoice->payments()->create($data);

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', __('ui.payments.messages.recorded'));
    }

    public function destroy(Payment $payment)
    {
        $this->ensurePaymentsTable();

        $invoice = $payment->invoice;
        $payment->delete();

        return redirect()->route('invoices.show', $invoice->number ?? $invoice->id)
            ->with('ok', __('ui.payments.messages.deleted'));
    }

    private function ensurePaymentsTable(): void
    {
        if (Schema::hasTable('payments')) {
            return;
        }

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('invoice_id');
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->nullable();
            $table->string('method', 50)->nullable();
            $table->string('reference')->nullable();
            $table->text('note')->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->string('status', 30)->default('cleared');
            $table->string('slip_path')->nullable();
            $table->unsignedBigInteger('bank_statement_id')->nullable();
            $table->timestamps();

            $table->index('invoice_id');
        });
    }
}
