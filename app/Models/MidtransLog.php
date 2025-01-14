<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MidtransLog extends Model
{
    protected $table = 'midtrans_logs';

    protected $fillable = [
        'order_id',
        'booking_id',
        'transaction_status',
        'payment_status',
        'midtrans_response'
    ];

    protected $casts = [
        'midtrans_response' => 'array'
    ];

    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id');
    }
} 