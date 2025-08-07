<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;

class GroupController extends Controller
{
    /**
     * API: GET /api/groups?q=...
     * Hasil: [{id, group_name, customer_count}]
     */
    public function index(Request $request)
    {
        $q = $request->query('q', '');

        // Query group (LIKE, case-insensitive) dan ambil jumlah customer
        $query = CustomerGroup::query()
            ->withCount('customers');

        if ($q) {
            $query->where('group_name', 'like', '%' . $q . '%');
        }

        $groups = $query->orderBy('group_name')->get()->map(function ($g) {
            return [
                'id' => $g->id,
                'group_name' => $g->group_name,
                'customer_count' => $g->customers_count,
            ];
        });

        return response()->json($groups);
    }
}
