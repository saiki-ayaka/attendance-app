<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RestTime;
use App\Models\Attendance;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\StoreStampCorrectionRequest;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    public function index()
    {
        $today_db = \Carbon\Carbon::now()->format('Y-m-d');
        $week = ['日', '月', '火', '水', '木', '金', '土'];
        $dayOfWeek = \Carbon\Carbon::now()->dayOfWeek;
        $today = \Carbon\Carbon::now()->format('Y年m月d日') . '(' . $week[$dayOfWeek] . ')';
        $time = \Carbon\Carbon::now()->format('H:i');
        $user_id = \Illuminate\Support\Facades\Auth::id();
        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today_db)
                                ->first();
        if (!$attendance) {
            $status = 'attendance_none';
        } else {
            $statusMap = [1 => 'working', 2 => 'resting', 3 => 'attendance_end'];
            $status = $statusMap[$attendance->work_status] ?? 'attendance_none';
        }
        return view('attendance.index', compact('status', 'today', 'time'));
    }

    public function workStart()
    {
        $today = Carbon::now()->format('Y-m-d');
        $user_id = Auth::id();
        $already_attended = Attendance::where('user_id', $user_id)
                                    ->where('date', $today)
                                    ->exists();
        if ($already_attended) {
            return redirect('/attendance')->with('error', '既に本日分の出勤打刻が完了しています。');
        }
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
        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today)
                                ->first();
        if ($attendance) {
            $attendance->update(['work_status' => 2]);
            RestTime::create([
                'attendance_id' => $attendance->id,
                'start_time'    => Carbon::now(),
            ]);
        }
        return redirect('/attendance')->with('attendance_message', '休憩を開始しました。');
    }

    public function restEnd()
    {
        $user_id = Auth::id();
        $today = Carbon::now()->format('Y-m-d');
        $attendance = Attendance::where('user_id', $user_id)
                                ->where('date', $today)
                                ->first();
        if ($attendance) {
            $attendance->update(['work_status' => 1]);
            RestTime::where('attendance_id', $attendance->id)
                    ->whereNull('end_time')
                    ->update(['end_time' => Carbon::now()]);
        }
        return redirect('/attendance')->with('attendance_message', '休憩を終了しました。');
    }

    public function list(Request $request)
    {
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($month);
        $prev_month = $currentDate->copy()->subMonth()->format('Y-m');
        $next_month = $currentDate->copy()->addMonth()->format('Y-m');
        $display_month = $currentDate->format('Y/m');
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
            $totalRestInSeconds = 0;
            $totalRest = '';
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
            if ($totalRestInSeconds > 0) {
                $totalRest = gmdate('H:i', $totalRestInSeconds);
            }
            $totalWork = '';
            if ($attendance && $attendance->start_time && $attendance->end_time) {
                $start = \Carbon\Carbon::parse($attendance->start_time);
                $end = \Carbon\Carbon::parse($attendance->end_time);
                $workSeconds = $end->diffInSeconds($start) - $totalRestInSeconds;
                if ($workSeconds < 0) {
                    $workSeconds = 0;
                }
                if ($workSeconds > 0) {
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
        $date = request('date');
        $attendance = ($id !== 'new') 
            ? Attendance::with('restTimes', 'user', 'stampCorrectionRequest')->find($id) 
            : null;

        if (!$attendance) {
            $attendance = (object)[
                'id' => null,
                'date' => $date,
                'start_time' => null,
                'end_time' => null,
                'user' => \Illuminate\Support\Facades\Auth::user(),
                'restTimes' => collect([]),
                'stampCorrectionRequest' => null,
                'exists' => false,
            ];
        } else {
            if (!isset($attendance->stampCorrectionRequest)) {
                $attendance->stampCorrectionRequest = null;
            }
        }


        return view('attendance.show', compact('attendance'));
    }

    public function update(StoreStampCorrectionRequest $request, $id)
    {
        $restData = [];
    
        for ($i = 1; $i <= 2; $i++) {
            $start = $request->input("attendance.$i.start_time");
            $end = $request->input("attendance.$i.end_time");
        
            if ($start && $end) {
                $restData["rest$i"] = [
                    'start_time' => $request->date . ' ' . $start . ':00',
                    'end_time'   => $request->date . ' ' . $end . ':00',
                ];
            }
        }

        StampCorrectionRequest::updateOrCreate(
            ['attendance_id' => $id],
            [
                'user_id'    => Auth::id(),
                'date'       => $request->date,
                'start_time' => $request->date . ' ' . $request->input('attendance.0.start_time') . ':00',
                'end_time'   => $request->date . ' ' . $request->input('attendance.0.end_time') . ':00',
                'remarks'    => $request->remarks,
                'rest_data'  => json_encode($restData),
                'status'     => 0,
            ]
        );
        return redirect('/attendance/list')->with('message', '修正申請を送信しました');
    }

    public function store(StoreStampCorrectionRequest $request)
    {

        $start = $request->input('attendance.0.start_time');
        $end = $request->input('attendance.0.end_time');
        
        $startDate = $request->date . ' ' . $start . ':00';
        $endDate = $request->date . ' ' . $end . ':00';
        
        $attendance = Attendance::create([
            'user_id'    => Auth::id(),
            'date'       => $request->date,
            'start_time' => $startDate,
            'end_time'   => $endDate,
            'work_status'     => 3,
        ]);

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

    public function applyRequest(StoreStampCorrectionRequest $request, $id)
    {
        $attendanceInputs = $request->input('attendance');
        $restData = [
            'rest1' => [
                'start_time' => $attendanceInputs[1]['start_time'] ?? null,
                'end_time'   => $attendanceInputs[1]['end_time'] ?? null,
            ],
            'rest2' => [
                'start_time' => $attendanceInputs[2]['start_time'] ?? null,
                'end_time'   => $attendanceInputs[2]['end_time'] ?? null,
            ],
        ];

        \App\Models\StampCorrectionRequest::updateOrCreate(
            ['attendance_id' => $id],
            [
                'user_id'    => Auth::id(),
                'date'       => $request->date,
                'start_time' => $request->date . ' ' . $attendanceInputs[0]['start_time'] . ':00',
                'end_time'   => $request->date . ' ' . $attendanceInputs[0]['end_time'] . ':00',
                'remarks'    => $request->remarks,
                'rest_data'  => json_encode($restData),
            'status'     => 0,
            ]
        );

        return redirect('/attendance/list')->with('message', '修正申請を送信しました');
    }
}
