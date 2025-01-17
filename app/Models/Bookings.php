<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Bookings extends Model
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone_number',
        'schedule_id',
        'booking_date',
        'payment_status',
        'final_price',
        'voucher_id',
        'specialdays_id',
        'description',
        'created_by_id',
        'updated_by_id',
        'payment_id',
        'redirect_url',
        'customer_type',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'booking_date' => 'datetime',
        'final_price' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'payment_id' => 'string'
    ];

    /**
     * Scope a query to filter bookings based on given filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (isset($filters['name'])) {
            $query->where('name', 'like', '%' . $filters['name'] . '%');
        }
        if (isset($filters['email'])) {
            $query->where('email', 'like', '%' . $filters['email'] . '%');
        }
        if (isset($filters['phone_number'])) {
            $query->where('phone_number', 'like', '%' . $filters['phone_number'] . '%');
        }
        if (isset($filters['schedule_id'])) {
            $query->where('schedule_id', $filters['schedule_id']);
        }
        if (isset($filters['booking_date'])) {
            $query->where('booking_date', '>=', $filters['booking_date']);
        }
        if (isset($filters['payment_status'])) {
            $query->where('payment_status', 'like', '%' . $filters['payment_status'] . '%');
        }
        if (isset($filters['final_price'])) {
            $query->where('final_price', $filters['final_price']);
        }
        if (isset($filters['voucher_id'])) {
            $query->where('voucher_id', $filters['voucher_id']);
        }
        if (isset($filters['specialdays_id'])) {
            $query->where('specialdays_id', $filters['specialdays_id']);
        }
        if (isset($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }
        if (isset($filters['created_by_id'])) {
            $query->where('created_by_id', $filters['created_by_id']);
        }
        if (isset($filters['updated_by_id'])) {
            $query->where('updated_by_id', $filters['updated_by_id']);
        }
    }

    /**
     * Get the user associated with the booking.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the schedule associated with the booking.
     */
    public function schedule()
    {
        return $this->belongsTo(Schedules::class, 'schedule_id');
    }

    /**
     * Get the voucher associated with the booking.
     */
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    /**
     * Get the special day associated with the booking.
     */
    public function specialDay()
    {
        return $this->belongsTo(SpecialDays::class, 'specialdays_id');
    }

    /**
     * Get the user who created this booking.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the user who updated this booking.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by_id');
    }

    /**
     * Get the passengers associated with the booking.
     */
    public function passengers()
    {
        return $this->hasMany(Passenger::class, 'booking_id');
    }
}
