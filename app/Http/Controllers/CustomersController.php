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
            ->select('id', 'name as text')
            ->orderBy('name');

        if ($request->filled('q')) {
            $q = $request->get('q');
            $query->where('name', 'like', "%{$q}%");
        }

        $rows = $query->limit(100)->get();

        return response()->json($rows);
    }

    // /api/customers/{customer}.json => ดูข้อมูลลูกค้า 1 ราย
    public function showJson($customerId)
    {
        $row = DB::table('customers')->where('id', $customerId)->first();

        if (!$row) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($row);
    }
}
