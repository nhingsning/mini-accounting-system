<?php

namespace App\Http\Controllers;

use App\Models\BankStatement;
use App\Models\Invoice;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BankStatementController extends Controller
{
    public function index()
    {
        $this->ensureTables();

        $statements = BankStatement::orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->take(80)
            ->get();

        return view('payments.bank-statements', compact('statements'));
    }

    public function import(Request $request)
    {
        $this->ensureTables();

        $data = $request->validate([
            'statement_file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $path = $request->file('statement_file')->store('bank-statements');
        $content = file($request->file('statement_file')->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($content as $index => $line) {
            $row = str_getcsv($line);
            if ($index === 0 && Str::contains(strtolower(implode(' ', $row)), ['date', 'amount'])) {
                continue; // header row
            }

            [$date, $description, $reference, $amount] = array_pad($row, 4, null);

            BankStatement::create([
                'transaction_date' => $date ? date('Y-m-d', strtotime($date)) : null,
                'description'      => $description,
                'reference'        => $reference,
                'amount'           => $amount ?? 0,
                'currency'         => config('app.currency', 'THB'),
                'status'           => 'unmatched',
                'source_file'      => $path,
            ]);
        }

        $matched = $this->autoMatch();

        return redirect()->route('bank-statements.index')
            ->with('ok', __('ui.payments.messages.bank_imported', ['count' => $matched]));
    }

    public function reconcile()
    {
        $this->ensureTables();

        $matched = $this->autoMatch();

        return redirect()->route('bank-statements.index')
            ->with('ok', __('ui.payments.messages.bank_reconciled', ['count' => $matched]));
    }

    private function autoMatch(): int
    {
        $matched = 0;
        $statements = BankStatement::where('status', 'unmatched')->get();

        foreach ($statements as $statement) {
            $tokens = collect([$statement->reference, $statement->description])
                ->filter()
                ->flatMap(fn ($text) => preg_split('/[\s,;]+/', $text))
                ->filter()
                ->unique();

            $invoice = null;
            foreach ($tokens as $token) {
                $invoice = Invoice::where('number', $token)->first();
                if ($invoice) {
                    break;
                }
            }

            if (!$invoice) {
                continue;
            }

            $invoice->recalculatePaymentTotals();
            $outstanding = $invoice->outstanding_total ?? ($invoice->total - ($invoice->paid_total ?? 0));

            if (abs((float) $statement->amount - (float) $outstanding) > 0.01) {
                continue;
            }

            $payment = $invoice->payments()->create([
                'amount'            => $statement->amount,
                'currency'          => $statement->currency ?? $invoice->currency,
                'method'            => 'bank_transfer',
                'reference'         => trim(($statement->reference ?: '') . ' ' . ($statement->description ?: '')) ?: null,
                'paid_at'           => $statement->transaction_date ?? now(),
                'status'            => 'reconciled',
                'bank_statement_id' => $statement->id,
            ]);

            $statement->update([
                'status'             => 'matched',
                'matched_invoice_id' => $invoice->id,
                'matched_payment_id' => $payment->id,
            ]);

            $matched++;
        }

        return $matched;
    }

    private function ensureTables(): void
    {
        if (!Schema::hasTable('bank_statements')) {
            Schema::create('bank_statements', function (Blueprint $table) {
                $table->id();
                $table->string('description')->nullable();
                $table->string('reference')->nullable();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 10)->nullable();
                $table->date('transaction_date')->nullable();
                $table->string('status', 30)->default('unmatched');
                $table->unsignedBigInteger('matched_invoice_id')->nullable();
                $table->unsignedBigInteger('matched_payment_id')->nullable();
                $table->string('source_file')->nullable();
                $table->timestamps();

                $table->index(['status', 'transaction_date']);
            });
        }

        if (!Schema::hasTable('payments')) {
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
}
