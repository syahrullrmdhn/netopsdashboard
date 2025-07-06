<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    // Ticket number generator: ABH(cid_abh)-tahun-(urutan per customer per tahun)
    protected static function booted()
    {
        static::creating(function ($ticket) {
            // Ambil customer terkait (pastikan sudah valid di controller)
            $customer = \App\Models\Customer::find($ticket->customer_id);

            // Tahun dari open_date, fallback ke tahun sekarang
            $year = $ticket->open_date
                ? \Carbon\Carbon::parse($ticket->open_date)->format('Y')
                : now()->format('Y');

            // Hitung ticket yang sudah ada di tahun tsb, untuk customer tsb
            $count = self::whereYear('open_date', $year)
                ->where('customer_id', $ticket->customer_id)
                ->count();

            $order = $count + 1; // ticket ini urutan berikutnya

            // Format: ABH(cid_abh)-tahun-(3 digit urut)
            $ticket->ticket_number = sprintf(
                'ABH%s-%s-%03d',
                $customer ? $customer->cid_abh : 'XXX',
                $year,
                $order
            );
        });
    }

    // Relasi-relasi
    public function customer()
    {
        return $this->belongsTo(\App\Models\Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function updates()
    {
        return $this->hasMany(\App\Models\TicketUpdate::class)
                    ->latest('created_at');
    }
}
