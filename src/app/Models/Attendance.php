<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // これをインポート！
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory; // ここに記述！

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
        // 勤怠IDと紐付く「承認待ち（status=0）」の最新の申請を取得する例
        return $this->hasOne(StampCorrectionRequest::class, 'attendance_id')
                    ->where('status', 0);
    }

    public function getRestTimeAttribute()
    {
        // 休憩開始と終了があれば計算、なければ0
        if ($this->rest_start && $this->rest_end) {
            $start = \Carbon\Carbon::parse($this->rest_start);
            $end = \Carbon\Carbon::parse($this->rest_end);
            return $start->diff($end)->format('%H:%I');
        }
        return '00:00';
    }

    public function getWorkTimeAttribute()
    {
        // 出勤と退勤があれば計算（休憩時間を引く処理は今回はシンプルに退勤-出勤とします）
        if ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            // 実働時間 = (退勤 - 出勤) - 休憩
            // ※休憩時間を引くロジックは必要に応じて追加してください
            return $start->diff($end)->format('%H:%I');
        }
        return '00:00';
    }
}
