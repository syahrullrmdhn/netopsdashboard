<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'open_date',
        'customer_id',
        'supplier_ticket_number',
        'start_time',
        'end_time',
        'issue_type',
        'service_detail',
        'problem_detail',
        'action_taken',
        'preventive_action',
        'sla_duration',
        'alert',
        'status',
        'user_id',
        'realtime_sla',
        'escalation',
    ];

    protected $casts = [
        'open_date'  => 'datetime',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'alert'      => 'boolean',
    ];

    /**
     * Otomatis generate ticket_number saat create:
     * Format: ABH{CID}-{tahun}-{urut 3 digit}
     * – CID dipotong sebelum spasi pertama, non-alnum dihapus, max 10 char.
     */
    protected static function booted()
    {
        static::creating(function ($ticket) {
            // 1) Ambil customer
            $customer = \App\Models\Customer::find($ticket->customer_id);

            // 2) Tahun dari open_date atau tahun sekarang
            $year = $ticket->open_date
                ? Carbon::parse($ticket->open_date)->format('Y')
                : now()->format('Y');

            // 3) Ambil customer group ID dari koneksi customerdb, lalu tambahkan "00" di belakang
            $groupSuffix = '0000'; // default jika tidak ditemukan (2 digit id + "00")
            if ($customer && $customer->customer_group_id) {
                $group = \App\Models\CustomerGroup::find($customer->customer_group_id);
                if ($group) {
                    // misal id = 61 → jadi "6100"
                    $groupSuffix = $group->id . '00';
                }
            }

            // 4) Hitung urutan tiket untuk customer + tahun tersebut
            $count = self::whereYear('open_date', $year)
                         ->where('customer_id', $ticket->customer_id)
                         ->count();
            $order = $count + 1;

            // 5) Bentuk ticket_number: YEAR + groupSuffix + urutan 3-digit
            //    Contoh: 2025 + "6100" + "007" = "20256100007"
            $ticket->ticket_number = $year
                                  . $groupSuffix
                                  . sprintf('%03d', $order);
        });
    }

    /** Relasi ke Customer */
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class, 'customer_id', 'id');
    }


    /** Relasi ke User (yang create ticket) */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /** Relasi ke TicketUpdate (chronology) */
    public function updates()
    {
        return $this->hasMany(\App\Models\TicketUpdate::class)
                    ->latest('created_at');
    }
}
