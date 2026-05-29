<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // これを追加！
use Illuminate\Database\Eloquent\Model;

class RestTime extends Model
{
    use HasFactory; // これを追加！

    // データベースのどのテーブルと紐づくか（通常は自動で判断されますが明示すると安心）
    protected $table = 'rest_times';

    protected $fillable = [
        'attendance_id', 
        'start_time', 
        'end_time'
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}
