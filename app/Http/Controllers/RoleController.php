<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;

class RoleController extends Controller
{
    /**
     * Mapping nama permission => label manusiawi
     */
    protected function permissionLabels(): array
    {
        return [
            // Ignition
            'ignition.healthCheck'       => 'Cek Kesehatan Sistem',
            'ignition.executeSolution'   => 'Eksekusi Solusi Ignition',
            'ignition.updateConfig'      => 'Perbarui Konfigurasi Ignition',

            // Auth
            'login'                      => 'Login',
            'logout'                     => 'Logout',
            'register'                   => 'Registrasi',

            // Dashboard
            'dashboard'                  => 'Lihat Dashboard',

            // Customers
            'customers.json'             => 'Data JSON Pelanggan',
            'customers.export'           => 'Export Data Pelanggan',
            'customers.index'            => 'Lihat Daftar Pelanggan',
            'customers.create'           => 'Form Buat Pelanggan',
            'customers.store'            => 'Simpan Pelanggan Baru',
            'customers.show'             => 'Lihat Detail Pelanggan',
            'customers.edit'             => 'Form Edit Pelanggan',
            'customers.update'           => 'Update Pelanggan',
            'customers.destroy'          => 'Hapus Pelanggan',

            // Tickets
            'tickets.index'              => 'Lihat Daftar Tiket',
            'tickets.create'             => 'Form Buat Tiket',
            'tickets.store'              => 'Simpan Tiket Baru',
            'tickets.show'               => 'Lihat Detail Tiket',
            'tickets.edit'               => 'Form Edit Tiket',
            'tickets.update'             => 'Update Tiket',
            'tickets.destroy'            => 'Hapus Tiket',
            'tickets.close'              => 'Tutup Tiket',
            'tickets.updates.store'      => 'Tambah Update Tiket',
            'tickets.rfo'                => 'Form Request for Outage',
            'tickets.rfo.pdf'            => 'Export RFO ke PDF',

            // Reports & Analytics
            'reports.index'              => 'Lihat Laporan & Analitik',
            'reports.exportPdf'          => 'Export Laporan ke PDF',
            'reports.print'              => 'Print Laporan',
            'reports.exportSpout'        => 'Export Laporan (Spout)',

            // SLA
            'sla.index'                  => 'Lihat SLA',
            'sla.device'                 => 'Detail SLA Per Perangkat',
            'sla.show'                   => 'Lihat SLA Sensor',
            'sla.downloadPdf'            => 'Download SLA ke PDF',

            // Performance
            'performance.index'          => 'Lihat Kinerja',
            'performance.eval'           => 'Dashboard Evaluasi Kinerja',
            'performance.detail'         => 'Detail Kinerja',

            // Escalation
            'escalations.index'          => 'Lihat Daftar Eskalasi',
            'escalations.store'          => 'Kirim Eskalasi',

            // Email Settings
            'settings.mail.edit'         => 'Form Edit Pengaturan Email',
            'settings.mail.update'       => 'Simpan Pengaturan Email',

            // NOC
            'noc.manageShifts'           => 'Kelola Shift NOC',
            'noc.handover'               => 'Form Handover Shift',
            'noc.history'                => 'Riwayat Handover Shift',

            // Roles & Users (CRUD)
            'roles.index'                => 'Lihat Daftar Role',
            'roles.create'               => 'Form Buat Role',
            'roles.store'                => 'Simpan Role Baru',
            'roles.edit'                 => 'Form Edit Role',
            'roles.update'               => 'Update Role',
            'roles.destroy'              => 'Hapus Role',

            'users.index'                => 'Lihat Daftar User',
            'users.create'               => 'Form Buat User',
            'users.store'                => 'Simpan User Baru',
            'users.edit'                 => 'Form Edit User',
            'users.update'               => 'Update User',
            'users.destroy'              => 'Hapus User',
        ];
    }

    public function __construct()
    {
        $this->middleware('permission:roles.index|roles.create|roles.edit|roles.destroy', ['only' => ['index','show']]);
        $this->middleware('permission:roles.create', ['only' => ['create','store']]);
        $this->middleware('permission:roles.edit', ['only' => ['edit','update']]);
        $this->middleware('permission:roles.destroy', ['only' => ['destroy']]);
    }

    /**
     * Optional: kalau Anda pakai auto-generate permission dari nama route
     */
    protected function generateRoutePermissions()
    {
        $names = collect(Route::getRoutes()->getIterator())
            ->map->getName()
            ->filter()
            ->unique();

        foreach ($names as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public function index()
    {
        $roles = Role::with('permissions')->get();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        // jika pakai auto-generate, uncomment baris ini
        // $this->generateRoutePermissions();

        $permissions = Permission::all();
        $labels      = $this->permissionLabels();

        return view('roles.create', compact('permissions','labels'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|unique:roles,name',
            'permissions' => 'array',
        ]);

        $role = Role::create([
            'name'       => $data['name'],
            'guard_name' => 'web',
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')
                         ->with('success','Role berhasil dibuat.');
    }

    public function edit(Role $role)
    {
        // Cek apakah user memiliki izin untuk mengedit role ini
        if (!auth()->user()->can('roles.edit', $role)) {
            abort(403);
        }

        $permissions     = Permission::all();
        $rolePermissions = $role->permissions->pluck('name')->toArray();
        $labels          = $this->permissionLabels();

        return view('roles.edit', compact('role','permissions','rolePermissions','labels'));
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name'        => 'required|unique:roles,name,'.$role->id,
            'permissions' => 'array',
        ]);

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')
                         ->with('success','Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return back()->with('success','Role berhasil dihapus.');
    }
}
