<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime;
use App\Models\StampCorrectionRequest;
use App\Http\Requests\UpdateAttendanceRequest;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date', Carbon::today()->format('Y-m-d'));
        $currentDate = Carbon::parse($date);
        $prevDate = $currentDate->copy()->subDay()->format('Y-m-d');
        $nextDate = $currentDate->copy()->addDay()->format('Y-m-d');
        $staffs = User::where('role', 1)->paginate(10);
        $attendances = Attendance::whereDate('date', $date)->get();

        foreach ($attendances as $attendance) {
            $restTotalSeconds = RestTime::where('attendance_id', $attendance->id)
                ->get()
                ->sum(function($rest) {
                    return Carbon::parse($rest->start_time)->diffInSeconds(Carbon::parse($rest->end_time));
                });
            
            $attendance->rest_total = gmdate('H:i', $restTotalSeconds);

            if ($attendance->start_time && $attendance->end_time) {
                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);
                $workSeconds = $end->diffInSeconds($start) - $restTotalSeconds;
                $attendance->work_total = gmdate('H:i', max(0, $workSeconds));
            } else {
                $attendance->work_total = '00:00';
            }
        }

        $attendances = $attendances->keyBy('user_id');

        return view('admin.attendance.list', compact('date', 'prevDate', 'nextDate', 'staffs', 'attendances'));
    }

    public function show(Request $request, $id)
    {
        if ($id == 0) {
            $date = $request->query('date') ?? date('Y-m-d');
            $userId = $request->query('user_id');
        
            $attendance = new Attendance();
            $attendance->date = $date;
            $attendance->user_id = $userId;
            $attendance->user = User::findOrFail($userId);
            $attendance->setRelation('restTimes', collect([]));
            $attendance->setRelation('stampCorrectionRequest', null);
        } else {
            $attendance = Attendance::with(['user', 'restTimes', 'stampCorrectionRequest'])->findOrFail($id);
        }
        $target = $attendance;
        return view('admin.attendance.show', compact('attendance', 'target'));
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $attendance->start_time = $attendance->date . ' ' . $request->input('attendance.0.start_time') . ':00';
        $attendance->end_time   = $attendance->date . ' ' . $request->input('attendance.0.end_time') . ':00';
        $attendance->remarks    = $request->remarks;
        $attendance->save();
        $this->saveRestTimes($attendance, $request);
        return redirect()->route('admin.attendance.list', ['date' => $attendance->date])
                    ->with('message', '勤怠を修正しました');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'attendance.0.start_time' => 'required',
            'attendance.0.end_time'   => 'required|after:attendance.0.start_time',
            'remarks'                 => 'required',
        ], [
            'attendance.0.end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'remarks.required'           => '備考を記入してください',
        ]);
        $attendance = Attendance::create([
            'user_id'    => $request->user_id,
            'date'       => $request->date, // 送信されてきた日付を使用
            'start_time' => $request->date . ' ' . $request->input('attendance.0.start_time') . ':00',
            'end_time'   => $request->date . ' ' . $request->input('attendance.0.end_time') . ':00',
            'remarks'    => $request->remarks,
        ]);
        $this->saveRestTimes($attendance, $request);

        return redirect()->route('admin.attendance.list', ['date' => $request->date])
                        ->with('message', '勤怠を登録しました');
    }

    private function saveRestTimes($attendance, $request)
    {
        $attendance->restTimes()->delete();

        for ($i = 1; $i <= 2; $i++) {
            $rest = $request->input("attendance.{$i}");

            if (!empty($rest['start_time']) && !empty($rest['end_time'])) {
                $attendance->restTimes()->create([
                    'attendance_id' => $attendance->id,
                    'start_time'    => $attendance->date . ' ' . $rest['start_time'] . ':00',
                    'end_time'      => $attendance->date . ' ' . $rest['end_time'] . ':00',
                ]);
            }
        }
    }

    public function staffAttendance(Request $request, $id)
    {
        $staff = User::findOrFail($id);
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($month);
        $display_month = $currentDate->format('Y/m');
        $prev_month = $currentDate->copy()->subMonth()->format('Y-m');
        $next_month = $currentDate->copy()->addMonth()->format('Y-m');
        $daysInMonth = $currentDate->daysInMonth;
        $attendances = [];
        $weekMap = ['日', '月', '火', '水', '木', '金', '土'];

        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = $currentDate->copy()->day($i);
            $record = Attendance::with('restTimes')
                                ->where('user_id', $id)
                                ->whereDate('date', $date)
                                ->first();
            $formattedDate = $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')';
            $restTotalSeconds = 0;
            if ($record) {
                $restTotalSeconds = $record->restTimes->sum(function($rest) {
                    return Carbon::parse($rest->start_time)->diffInSeconds(Carbon::parse($rest->end_time));
                });
            }
            $total_rest = $restTotalSeconds > 0 ? gmdate('H:i', $restTotalSeconds) : '';
            $total_work = '';
            if ($record && $record->start_time && $record->end_time) {
                $start = Carbon::parse($record->start_time);
                $end = Carbon::parse($record->end_time);
                $workSeconds = $end->diffInSeconds($start) - $restTotalSeconds;
                $total_work = gmdate('H:i', max(0, $workSeconds));
            }
            $attendances[] = [
                'id' => $record ? $record->id : $date->format('Y-m-d'),
                'date' => $formattedDate,
                'start_time' => $record ? Carbon::parse($record->start_time)->format('H:i') : '',
                'end_time' => $record ? Carbon::parse($record->end_time)->format('H:i') : '',
                'total_rest' => $total_rest,
                'total_work' => $total_work,
            ];
        }
        return view('admin.attendance.staff', compact(
            'staff', 'attendances', 'display_month', 'prev_month', 'next_month'
        ));
    }

    public function export(Request $request, $id)
    {
        $staff = User::findOrFail($id);
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $currentDate = Carbon::parse($month);
        $attendances = Attendance::where('user_id', $id)
            ->whereYear('date', $currentDate->year)
            ->whereMonth('date', $currentDate->month)
            ->orderBy('date', 'asc')
            ->get();
        $filename = "attendance_{$staff->name}_{$month}.csv";
        $response = new StreamedResponse(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);
            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->date,
                    $attendance->start_time,
                    $attendance->end_time,
                    $attendance->rest_time,
                    $attendance->work_time,
                ]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
        return $response;
    }

    public function requestList(Request $request)
    {
        $tab = $request->query('tab', 'waiting');
        $status = ($tab === 'approved') ? 1 : 0;
        $requests = StampCorrectionRequest::where('status', $status)
            ->with(['user', 'attendance'])
            ->latest()
            ->paginate(10);
        return view('admin.request.list', compact('requests', 'tab'));
    }

    public function approveRequest($id)
    {
        $req = \App\Models\StampCorrectionRequest::with(['user', 'attendance'])->findOrFail($id);
        $attendance = $req->attendance;
        if (!$attendance) abort(404);

        $rawData = json_decode($req->rest_data, true) ?? [];
        $restData = [
            'rest1' => $rawData[0] ?? null,
            'rest2' => $rawData[1] ?? null,
        ];

        $isApprovalMode = true;
        return view('admin.request.approve', compact('req', 'attendance', 'isApprovalMode', 'restData'));
    }

    public function updateRequest(Request $request, $id)
    {
        $req = \App\Models\StampCorrectionRequest::findOrFail($id);
        $attendance = $req->attendance;

        \DB::transaction(function () use ($req, $attendance) {
            $attendance->update([
                'start_time' => $req->start_time,
                'end_time'   => $req->end_time,
            ]);
            \App\Models\RestTime::where('attendance_id', $attendance->id)->delete();
            $restData = json_decode($req->rest_data, true);
            if ($restData) {
                foreach ($restData as $restItem) {
                    if (is_array($restItem) && !empty($restItem['start_time']) && !empty($restItem['end_time'])) {
                        \App\Models\RestTime::create([
                            'attendance_id' => $attendance->id,
                            'start_time'    => $restItem['start_time'],
                            'end_time'      => $restItem['end_time'],
                        ]);
                    }
                }
            }
            $req->update(['status' => 1]);
        });
        return redirect()->route('admin.request.list')->with('success', '承認が完了しました');
    }
}