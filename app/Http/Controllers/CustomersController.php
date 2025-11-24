<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomersController extends Controller
{
    // /api/customers/options => สำหรับ dropdown
    public function options(Request $request)
    {
        $query = DB::table('customers')
            ->select('id', 'name as text', 'tax_id')
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = trim($request->get('q'));
            $query->where(function ($qq) use ($q) {
                $qq->where('name', 'like', "%{$q}%")
                   ->orWhere('tax_id', 'like', "%{$q}%");
            });
        }

        $rows = $query->limit(50)->get()->map(function ($row) {
            $label = $row->text;
            if ($row->tax_id) {
                $label .= " ({$row->tax_id})";
            }
            $row->text = $label;
            return $row;
        });

        return response()->json($rows);
    }

    // /api/customers/{customer}.json => ดูข้อมูลลูกค้า 1 ราย
    public function showJson($customerId)
    {
        $row = DB::table('customers')->where('id', $customerId)->first();

        if (!$row) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $contact = DB::table('customer_contacts')
            ->where('customer_id', $customerId)
            ->orderBy('id')
            ->first();

        return response()->json([
            'customer' => $row,
            'contact'  => $contact,
        ]);
    }
}
