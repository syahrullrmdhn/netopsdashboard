<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use App\Models\Customer;
use App\Models\CustomerGroup;

class Ticket extends Model
{
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
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

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'open_date'  => 'datetime',
        'start_time' => 'datetime',
        'end_time'   => 'datetime',
        'alert'      => 'boolean',
    ];

    /**
     * Booted model hook: generate ticket_number on create.
     */
    protected static function booted()
    {
        static::creating(function (Ticket $ticket) {
            $ticket->ticket_number = self::generateTicketNumber($ticket);
        });
    }

    /**
     * Generate a unique ticket number in the format:
     *   {YEAR}{customer_group_id}00{3‑digit sequence}
     *
     * Example: 2025 + "36" + "00" + "007" → "20253600007"
     */
    protected static function generateTicketNumber(Ticket $ticket): string
    {
        // 1) Year part
        $year = $ticket->open_date
            ? Carbon::parse($ticket->open_date)->format('Y')
            : Carbon::now()->format('Y');

        // 2) Customer‑group ID + "00" suffix
        $groupSuffix = '0000'; // default if no group
        if ($ticket->customer_id) {
            $cust = Customer::find($ticket->customer_id);
            if ($cust && $cust->customer_group_id) {
                $groupSuffix = $cust->customer_group_id . '00';
            }
        }

        // 3) Prefix assembly
        $prefix = $year . $groupSuffix;

        // 4) Find last existing ticket_number with this prefix
        $last = self::where('ticket_number', 'like', "{$prefix}%")
                    ->orderBy('ticket_number', 'desc')
                    ->first();

        // 5) Determine next sequence
        if ($last) {
            $lastSeq = (int) substr($last->ticket_number, strlen($prefix));
            $nextSeq = $lastSeq + 1;
        } else {
            $nextSeq = 1;
        }

        // 6) Pad to 3 digits
        $suffix = str_pad($nextSeq, 3, '0', STR_PAD_LEFT);

        return $prefix . $suffix;
    }

    /**
     * Relationship: Ticket belongs to a Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Relationship: Ticket belongs to a User (creator).
     */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Relationship: Ticket has many updates (chronology entries).
     */
    public function updates()
    {
        return $this->hasMany(\App\Models\TicketUpdate::class)
                    ->latest('created_at');
    }
}
