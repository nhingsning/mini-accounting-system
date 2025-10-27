<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    // ===== List + Search =====
    public function index()
    {
        $q = request('q');

        $invoices = Invoice::query()
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
        $invoice = ctype_digit($key)
            ? Invoice::find($key)
            : Invoice::where('number',$key)->first();

        abort_unless($invoice, 404, 'Invoice not found');
        return $invoice;
    }

<<<<<<< HEAD
    // ===== Show (HTML view) =====
    public function show(string $key)
=======
    public function show(Invoice $invoice)
    {
        return redirect()->route('invoices.edit', $invoice);
    }

    public function store(Request $request)
>>>>>>> 31866941e8e10f3e8320fc8f3e315afac769fadc
    {
        $invoice = $this->findByKey($key);
        return view('invoices.show', compact('invoice'));
    }

    // ===== Edit form (HTML view) =====
    public function edit(string $key)
    {
        $invoice = $this->findByKey($key);
        return view('invoices.edit', compact('invoice'));
    }

    // ===== Update action =====
    public function update(Request $request, string $key)
    {
        $invoice = $this->findByKey($key);

        $data = $request->validate([
            'customer_name' => 'required|string|max:255',
            'issue_date'    => 'nullable|date',
            'status'        => 'required|string|max:50',
            'total'         => 'required|numeric',
        ]);

        $invoice->update($data);

        // ให้ redirect กลับไปหน้า show โดยใช้เลขเอกสารถ้ามี
        $slug = $invoice->number ?: $invoice->id;
        return redirect()->route('invoices.show', $slug)->with('ok','Updated');
    }
}
