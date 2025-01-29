<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'tanggal',
        'alasan',
        'estimasi_refund',
        'persentase'
    ];

    protected $casts = [
        'tanggal' => 'date',
        'estimasi_refund' => 'decimal:2',
        'persentase' => 'decimal:2'
    ];

    public function booking()
    {
        return $this->belongsTo(Bookings::class, 'booking_id');
    }
} 