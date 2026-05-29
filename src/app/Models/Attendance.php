<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // これをインポート！
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'date', 'work_status', 'start_time', 'end_time', 'remarks'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function restTimes()
    {
        return $this->hasMany(RestTime::class);
    }

    public function stampCorrectionRequest()
    {
        return $this->hasOne(StampCorrectionRequest::class, 'attendance_id')
                    ->where('status', 0);
    }

    public function getRestTimeAttribute()
    {
        if ($this->rest_start && $this->rest_end) {
            $start = \Carbon\Carbon::parse($this->rest_start);
            $end = \Carbon\Carbon::parse($this->rest_end);
            return $start->diff($end)->format('%H:%I');
        }
        return '00:00';
    }

    public function getWorkTimeAttribute()
    {
        if ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            return $start->diff($end)->format('%H:%I');
        }
        return '00:00';
    }
}
