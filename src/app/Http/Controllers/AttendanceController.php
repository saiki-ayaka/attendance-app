<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestTime;
use App\Models\Attendance; // 追加
use App\Models\StampCorrectionRequest;
use App\Http\Requests\StoreStampCorrectionRequest;
use Illuminate\Support\Facades\Auth; // 追加
use Carbon\Carbon; // 追加

class AttendanceController extends Controller
{
    public function index()
    {
        // 1. データベース検索用の日付
        $today_db = \Carbon\Carbon::now()->format('Y-m-d');
    
        // 2. 画面表示用の日付（変数名を $today に統一！）
        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = \Carbon\Carbon::now()->dayOfWeek;
        $today = \Carbon\Carbon::now()->format('Y年m月d日') . '(' . $week[$dayOfWeek] . ')';
    
        $time = \Carbon\Carbon::now()->format('H:i');
        $user_id = \Illuminate\Support\Facades\Auth::id();

        // データベース検索
        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today_db)
                                ->first();

        // ステータス判定
        if (!$attendance) {
            $status = 'attendance_none';
        } else {
            $statusMap = [1 => 'working', 2 => 'resting', 3 => 'attendance_end'];
            $status = $statusMap[$attendance->work_status] ?? 'attendance_none';
        }

        // ここで compact('status', 'today', 'time') とすればエラーは消えます
        return view('attendance.index', compact('status', 'today', 'time'));
    }

    public function workStart()
    {
        $today = Carbon::now()->format('Y-m-d');
        $user_id = Auth::id();

        // ★追加：すでに今日出勤しているかチェック
        $already_attended = Attendance::where('user_id', $user_id)
                                    ->where('date', $today)
                                    ->exists();

        if ($already_attended) {
            return redirect('/attendance')->with('error', '既に本日分の出勤打刻が完了しています。');
        }

        // データベースに新しい勤怠レコードを作成
        Attendance::create([
            'user_id'    => $user_id,
            'date'       => $today,
            'work_status'     => 1,
            'start_time' => Carbon::now(),
        ]);

        return redirect('/attendance')->with('attendance_message', '出勤打刻が完了しました！');
    }

    public function workEnd()
    {
        $user_id = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        // 今日の勤怠データを取得して、statusを「3（退勤済）」にする
        Attendance::where('user_id', $user_id)
                ->where('date', $today)
                ->update([
                    'work_status' => 3,
                    'end_time' => Carbon::now(),
                ]);

        return redirect('/attendance')->with('attendance_message', 'お疲れ様でした。');
    }

    public function restStart()
    {
        $user_id = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        // 1. 今日の勤怠データを取得
        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today)
                                ->first();

        if ($attendance) {
            // 2. 勤怠ステータスを「休憩中（2）」に変更
            $attendance->update(['work_status' => 2]);

            // 3. 休憩開始時刻を記録するレコードを作成
            RestTime::create([
                'attendance_id' => $attendance->id,
                'start_time'    => Carbon::now(),
                // end_time は休憩終了時に更新するため、ここでは空（NULL）でOK
            ]);
        }

        return redirect('/attendance')->with('attendance_message', '休憩を開始しました。');
    }

    // 休憩終了（休憩戻る）
    public function restEnd()
    {
        $user_id = Auth::id();
        $today = Carbon::now()->format('Y-m-d');

        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today)
                                ->first();

        if ($attendance) {
            // 1. ステータスを「出勤中（1）」に戻す
            $attendance->update(['work_status' => 1]);

            // 2. まだ終了していない休憩レコード（end_timeがNULL）を取得して終了時刻を入れる
            RestTime::where('attendance_id', $attendance->id)
                    ->whereNull('end_time')
                    ->update(['end_time' => Carbon::now()]);
        }

        return redirect('/attendance')->with('attendance_message', '休憩を終了しました。');
    }

    public function list(Request $request)
    {
        // 指定月または今月を取得
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($month);

        // 前月・翌月の計算
        $prev_month = $currentDate->copy()->subMonth()->format('Y-m');
        $next_month = $currentDate->copy()->addMonth()->format('Y-m');
        $display_month = $currentDate->format('Y/m'); // Y/m に変更

        // その月の日数を取得
        $daysInMonth = $currentDate->daysInMonth;

        $attendances = [];
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $currentDate->copy()->addDays($i - 1);
            $dateStr = $date->format('Y-m-d');
        
            $week = ['日', '月', '火', '水', '木', '金', '土'];
            $dayOfWeek = $week[$date->dayOfWeek];

            $attendance = Attendance::where('user_id', Auth::id())
                                    ->where('date', $dateStr)
                                    ->first();

            // 休憩時間の計算
            $totalRestInSeconds = 0;
            $totalRest = ''; // 初期値を空文字にする

            if ($attendance) {
                $restTimes = RestTime::where('attendance_id', $attendance->id)->get();
                foreach ($restTimes as $rest) {
                    if ($rest->start_time && $rest->end_time) {
                        $start = \Carbon\Carbon::parse($rest->start_time);
                        $end = \Carbon\Carbon::parse($rest->end_time);
                        $totalRestInSeconds += $end->diffInSeconds($start);
                    }
                }
            }
            // 休憩時間が0秒より大きい場合のみ値をセット
            if ($totalRestInSeconds > 0) {
                $totalRest = gmdate('H:i', $totalRestInSeconds);
            }

            // 合計労働時間の計算
            $totalWork = ''; // 初期値を空文字にする

            if ($attendance && $attendance->start_time && $attendance->end_time) {
                $start = \Carbon\Carbon::parse($attendance->start_time);
                $end = \Carbon\Carbon::parse($attendance->end_time);
    
                // 労働時間 = (退勤 - 出勤) - 合計休憩時間
                $workSeconds = $end->diffInSeconds($start) - $totalRestInSeconds;
    
                // 1. マイナスにならないようにガード
                if ($workSeconds < 0) {
                    $workSeconds = 0;
                }
                
                // 2. 労働時間が0秒より大きい場合のみ値をセット
                if ($workSeconds > 0) {
                    // 24時間以上の労働にも対応した正確な形式変換
                    $hours = floor($workSeconds / 3600);
                    $minutes = floor(($workSeconds % 3600) / 60);
                    $totalWork = sprintf('%02d:%02d', $hours, $minutes);
                }
            }

            $attendances[] = (object)[
                'id' => $attendance->id ?? null,
                'raw_date' => $dateStr,
                'date' => $date->format('m/d') . '(' . $dayOfWeek . ')',
                'start_time' => $attendance && $attendance->start_time 
                                ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : null,
                'end_time' => $attendance && $attendance->end_time 
                                ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : null,
                'total_rest' => $totalRest,
                'total_work' => $totalWork,
            ];
        }

        return view('attendance.list', compact('attendances', 'prev_month', 'next_month', 'display_month'));
    }

    public function show($id)
    {
        // IDが 'new' の場合（新規作成用）
        if ($id === 'new') {
            $attendance = (object)[
                'date' => request('date'),
                'start_time' => null,
                'end_time' => null,
                'status' => null,
                'work_status' => null,
                'restTimes' => collect([]),
                'user' => \Illuminate\Support\Facades\Auth::user(),
                'stampCorrectionRequest' => null,
            ];
        } else {
            // IDが数字の場合（既存データの編集用）
            $attendance = Attendance::with('restTimes', 'user', 'stampCorrectionRequest')->findOrFail($id);
            // ビューが期待している stampCorrectionRequest がない場合の対策
            if (!isset ($attendance->stampCorrectionRequest)) {
                $attendance->stampCorrectionRequest = null;

            }
        }

        return view('attendance.show', compact('attendance'));
    }

    /**
     * 勤怠修正申請の登録
     */
    public function update(StoreStampCorrectionRequest $request, $id)
    {
        // 1. 日付と時間を結合して正しい datetime 形式にする
        $startDate = $request->date . ' ' . $request->start_time . ':00';
        $endDate = $request->date . ' ' . $request->end_time . ':00';

        // 休憩データも同様に結合が必要ならここで行う
        $restData = [
            'rest1' => [
                'start_time' => $request->attendance[1]['start_time'] ? $request->date . ' ' . $request->attendance[1]['start_time'] . ':00' : null,
                'end_time'   => $request->attendance[1]['end_time']   ? $request->date . ' ' . $request->attendance[1]['end_time'] . ':00'   : null,
            ],
            'rest2' => [
                'start_time' => $request->attendance[2]['start_time'] ? $request->date . ' ' . $request->attendance[2]['start_time'] . ':00' : null,
                'end_time'   => $request->attendance[2]['end_time']   ? $request->date . ' ' . $request->attendance[2]['end_time'] . ':00'   : null,
            ],
        ];

        // 2. 申請データの保存
        StampCorrectionRequest::updateOrCreate(
            ['attendance_id' => $id],
            [
                'user_id'       => Auth::id(),
                'date'          => $request->date,
                'start_time'    => $startDate, // 結合したものを使う
                'end_time'      => $endDate,   // 結合したものを使う
                'remarks'       => $request->remarks,
                'rest_data'     => json_encode($restData),
                'status'        => 0,
            ]
        );

        return redirect('/attendance/list')->with('message', '修正申請を送信しました');
    }
    
    public function store(StoreStampCorrectionRequest $request)
    {
        // 1. バリデーション
        $request->validate([
            'start_time' => 'required',
            'end_time'   => 'required|after:start_time',
            'remarks'    => 'required',
        ]);

        // 2. 日付と時刻を結合して正しいdatetime形式にする
        $startDate = $request->date . ' ' . $request->start_time . ':00';
        $endDate = $request->date . ' ' . $request->end_time . ':00';

        // 3. 勤怠レコードを作成
        $attendance = Attendance::create([
            'user_id'    => Auth::id(),
            'date'       => $request->date,
            'start_time' => $startDate, // 結合した日時を入れる
            'end_time'   => $endDate,   // 結合した日時を入れる
            'work_status'     => 3,
        ]);

        // 4. 申請データの登録（申請用も同様に結合したものを使う）
        StampCorrectionRequest::create([
            'user_id'       => Auth::id(),
            'attendance_id' => $attendance->id,
            'date'          => $request->date,
            'status'        => 0,
            'start_time'    => $startDate,
            'end_time'      => $endDate,
            'remarks'       => $request->remarks,
        ]);

        if ($request->has('attendance')) {
            foreach ($request->attendance as $key => $rest) {
                // 休憩の入力がある場合のみ保存（'1'と'2'が休憩のインデックスなら）
                if ($key >= 1 && !empty($rest['start_time']) && !empty($rest['end_time'])) {
                    RestTime::create([
                        'attendance_id' => $attendance->id,
                        'start_time'    => $request->date . ' ' . $rest['start_time'] . ':00',
                        'end_time'      => $request->date . ' ' . $rest['end_time'] . ':00',
                    ]);
                }
            }
        }

        return redirect('/attendance/list')->with('message', '勤怠を新規登録しました');
    }
}
