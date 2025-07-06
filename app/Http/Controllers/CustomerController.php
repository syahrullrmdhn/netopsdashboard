<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Supplier;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
      public function index(Request $request)
    {
        // Base query with relations
        $query = Customer::with(['supplier','serviceType','group']);

        // Filters
        if ($request->filled('search')) {
            $query->where('customer', 'like', '%'.$request->search.'%');
        }
        if ($request->filled('group_id')) {
            $query->where('customer_group_id', $request->group_id);
        }
        if ($request->filled('status')) {
            $query->where('status', (int)$request->status);
        }

        // Paginate, append the query string so filters persist
        $customers = $query
            ->latest('id')
            ->paginate(15)
            ->appends($request->query());

        // For the filter dropdowns
        $groupsList = CustomerGroup::all();
        $statuses = [
            1 => 'Active',
            2 => 'Pending',
            3 => 'Suspended',
            4 => 'Terminated',
        ];

        return view('customers.index', compact('customers','groupsList','statuses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer'            => 'required|string|max:100',
            'cid_abh'             => 'required|string|max:50',
            'kdsupplier'          => 'required|exists:customerdb.suppliers,kdsupplier',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'extra_desc'          => 'nullable|string',
            'status'              => 'required|in:1,2,3,4',
            'customer_group_id'   => 'required|exists:customerdb.customer_groups,id',
            'service_type_id'     => 'required|exists:customerdb.service_types,id',
            'vlan'                => 'nullable|string',
            'ip_address'          => 'nullable|string',
            'prefix'              => 'nullable|string',
            'xconnect_id'         => 'nullable|string',
        ]);

        // hitung contract_period di accessor
        $data['contract_period'] = null; 
        $data['auto_renewal']    = 0;

        Customer::create($data);

        return redirect()->route('customers.index')
                         ->with('success', 'Customer created.');
    }

    public function edit(Customer $customer)
    {
        $suppliers = Supplier::all();
        return view('customers.edit', compact('customer','suppliers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'customer'            => 'required|string|max:100',
            'cid_abh'             => 'required|string|max:50',
            'kdsupplier'          => 'required|exists:customerdb.suppliers,kdsupplier',
            'start_date'          => 'required|date',
            'end_date'            => 'nullable|date|after_or_equal:start_date',
            'extra_desc'          => 'nullable|string',
            'status'              => 'required|in:1,2,3,4',
            'customer_group_id'   => 'required|exists:customerdb.customer_groups,id',
            'service_type_id'     => 'required|exists:customerdb.service_types,id',
            'vlan'                => 'nullable|string',
            'ip_address'          => 'nullable|string',
            'prefix'              => 'nullable|string',
            'xconnect_id'         => 'nullable|string',
        ]);

        $customer->update($data);

        return redirect()->route('customers.index')
                         ->with('success', 'Customer updated.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return redirect()->route('customers.index')
                         ->with('success', 'Customer deleted.');
    }
   public function json(Request $req)
{
    $q = $req->query('q','');
    $list = \App\Models\Customer::where('customer', 'like', "%{$q}%")
        ->orWhere('cid_abh','like',"%{$q}%")
        ->limit(50)
        ->get(['id','customer','cid_abh']);

    return response()->json($list);
}

}