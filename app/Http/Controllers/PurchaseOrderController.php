<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $q = request('q');
        $orders = PurchaseOrder::query()
            ->where('number','like','PO%')
            ->search($q)
            ->select('id','number','customer_name','issue_date','status','total','created_at')
            ->orderByDesc('issue_date')
            ->paginate(15);

        return view('po.index', [
            'orders' => $orders,
            'q' => $q,
        ]);
    }

    private function findByKey(string $key): PurchaseOrder
    {
        $po = ctype_digit($key)
            ? PurchaseOrder::find($key)
            : PurchaseOrder::where('number', $key)->first();

        abort_unless($po, 404, 'PO not found');
        return $po;
    }

    public function create()
    {
        return view('po.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'number'               => ['nullable','string','max:255', Rule::unique('invoices','number')],
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
            'status'               => 'nullable|string|max:50',
            'currency'             => 'nullable|string|max:10',
        ]);

        $data['vat_enabled'] = $request->boolean('vat_enabled');
        $data['number'] = $data['number'] ?? $this->nextNumber();

        $po = PurchaseOrder::create($data);

        $slug = $po->number ?: $po->id;

        return redirect()
            ->route('po.show', $slug)
            ->with('ok', 'PO created');
    }

    public function show(string $key)
    {
        $po = $this->findByKey($key);
        return view('po.show', compact('po'));
    }

    public function edit(string $key)
    {
        $po = $this->findByKey($key);
        return view('po.edit', compact('po'));
    }

    public function update(Request $request, string $key)
    {
        $po = $this->findByKey($key);

        $data = $request->validate([
            'number'        => ['nullable','string','max:255', Rule::unique('invoices','number')->ignore($po->id)],
            'customer_name' => 'required|string|max:255',
            'issue_date'    => 'nullable|date',
            'status'        => 'required|string|max:50',
            'total'         => 'required|numeric',
        ]);

        $po->update($data);

        $slug = $po->number ?: $po->id;
        return redirect()->route('po.show', $slug)->with('ok','PO updated');
    }

    public function destroy(string $key)
    {
        $po = $this->findByKey($key);
        $po->delete();

        return redirect()->route('po.index')->with('ok', 'PO deleted');
    }

    private function nextNumber(): string
    {
        $period = now()->format('Y-m');
        $prefix = 'PO'.$period.'-';
        $lastNo = PurchaseOrder::where('number','like',$prefix.'%')->orderByDesc('id')->value('number');
        $seq = 0;
        if ($lastNo && preg_match('/-(\d{4})$/', $lastNo, $m)) $seq = (int)$m[1];
        $seq++;
        return $prefix.str_pad((string)$seq, 4, '0', STR_PAD_LEFT);
    }
}
