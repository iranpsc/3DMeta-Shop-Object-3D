<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'status',
        'priority',
        'attachment',
        'closed_at',
        'user_id',
        'response_status',
    ];

    public function responses()
    {
        return $this->hasMany(TicketResponse::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isOpen()
    {
        return $this->status === 'open';
    }

    public function isClosed()
    {
        return $this->status === 'closed';
    }

    public function isHighPriority()
    {
        return $this->priority === 'high';
    }

    public function isLowPriority()
    {
        return $this->priority === 'low';
    }

    public function isNormalPriority()
    {
        return $this->priority === 'medium';
    }

    public function close()
    {
        $this->update(['status' => 'closed', 'closed_at' => now()]);
    }

    public function getPriorityTitleAttribute()
    {
        switch ($this->attributes['priority']) {
            case 'high':
                return 'بالا';
                break;
            case 'medium':
                return 'متوسط';
                break;
            case 'low':
                return 'پایین';
                break;
        }
    }

    public function getResponseStatusAttribute()
    {
        switch ($this->attributes['response_status']) {
            case 'pending':
                return 'درحال بررسی';
                break;
            case 'replied':
                return 'پاسخ داده شده';
                break;
        }
    }

    public function getStatusAttribute()
    {
        switch ($this->attributes['status']) {
            case 'open':
                return 'باز';
                break;
            case 'closed':
                return 'بسته';
                break;
        }
    }
}
