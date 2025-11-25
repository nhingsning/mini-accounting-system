<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $customers = Customer::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($w) use ($search) {
                    $w->where('name', 'like', "%{$search}%")
                        ->orWhere('tax_id', 'like', "%{$search}%")
                        ->orWhere('tel', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->appends(['search' => $search]);

        return view('customers.index', compact('customers', 'search'));
    }

    public function create()
    {
        return view('customers.form', ['customer' => new Customer()]);
    }

    public function store(Request $r)
    {
        // ใส่ validated() ของหนิงเอง
        $data = $this->validated($r);
        $c = Customer::create($data);

        $contacts = $r->input('contacts', []);
        $c->contacts()->createMany(collect($contacts)->filter(fn($x)=>!empty($x['contact_name']))->all());

        return redirect()->route('customers.index')->with('ok','Customer created');
    }

    public function edit(Customer $customer)
    {
        $customer->load('contacts');
        return view('customers.form', compact('customer'));
    }

    public function update(Request $r, Customer $customer)
    {
        $data = $this->validated($r);
        $customer->update($data);

        $customer->contacts()->delete();
        $contacts = $r->input('contacts', []);
        $customer->contacts()->createMany(collect($contacts)->filter(fn($x)=>!empty($x['contact_name']))->all());

        return redirect()->route('customers.index')->with('ok','Customer updated');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return back()->with('ok','Deleted');
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name'            => ['required','string','max:255'],
            'address_show'    => ['required','string'],
            'name_private'    => ['nullable','string','max:255'],
            'address_private' => ['nullable','string'],
            'tax_id'          => ['nullable','string','max:32'],
            'tel'             => ['required','string','max:64'],
            'fax'             => ['nullable','string','max:64'],
            'payment_terms'   => ['required','string','max:128'],
            'office_type'     => ['required','in:head,branch'],
            'branch_code'     => ['required_if:office_type,branch','nullable','string','max:64'],

            'contacts'                    => ['array'],
            'contacts.*.contact_name'     => ['nullable','string','max:255'],
            'contacts.*.department'       => ['nullable','string','max:255'],
            'contacts.*.position'         => ['nullable','string','max:255'],
            'contacts.*.mobile'           => ['nullable','string','max:64'],
            'contacts.*.email'            => ['nullable','email','max:255'],
        ]);
    }
    public function show(Customer $customer)
{
    $customer->load('contacts');
    return view('customers.show', compact('customer'));
}

// app/Http/Controllers/CustomersController.php
public function options(Request $req)
{
    $q = trim((string)$req->query('q', ''));

    $rows = \App\Models\Customer::query()
        ->when($q !== '', function ($qq) use ($q) {
            $qq->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('tax_id', 'like', "%{$q}%")
                  ->orWhere('tel', 'like', "%{$q}%");
            });
        })
        ->orderBy('name')
        ->limit(50)
        ->get(['id','name','tax_id']);

    return response()->json(
        $rows->map(fn($c)=>[
            'id'   => $c->id,
            'text' => $c->name . ($c->tax_id ? " ({$c->tax_id})" : ''),
        ])
    );
}

    // รายละเอียดลูกค้าเต็ม ๆ
    public function showJson(Customer $customer)
    {
        return response()->json([
            'id'             => $customer->id,
            'name'           => $customer->name,
            'name_hidden'    => $customer->name_hidden,
            'address'        => $customer->address,
            'address_hidden' => $customer->address_hidden,
            'tax_id'         => $customer->tax_id,
            'tel'            => $customer->tel,
            'fax'            => $customer->fax,
            'payment_terms'  => $customer->payment_terms,
            'is_branch'      => (bool)$customer->is_branch,
            'branch_code'    => $customer->branch_code,
            'contact' => [
                'name'       => $customer->contact_name,
                'department' => $customer->contact_department,
                'position'   => $customer->contact_position,
                'mobile'     => $customer->contact_mobile,
                'email'      => $customer->contact_email,
            ]
        ]);
    }
}
