<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use App\Models\RestTime; // ★追加
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

        // 勤怠データを取得
        $attendances = Attendance::whereDate('date', $date)->get();

        // ★計算処理を追加
        foreach ($attendances as $attendance) {
            // 休憩時間の合計(秒)を取得
            $restTotalSeconds = RestTime::where('attendance_id', $attendance->id)
                ->get()
                ->sum(function($rest) {
                    return Carbon::parse($rest->start_time)->diffInSeconds(Carbon::parse($rest->end_time));
                });
            
            // 休憩時間を 'H:i' 形式に変換
            $attendance->rest_total = gmdate('H:i', $restTotalSeconds);

            // 合計労働時間の計算
            if ($attendance->start_time && $attendance->end_time) {
                $start = Carbon::parse($attendance->start_time);
                $end = Carbon::parse($attendance->end_time);
                
                // (退勤 - 出勤) - 休憩時間 = 実働時間
                $workSeconds = $end->diffInSeconds($start) - $restTotalSeconds;
                $attendance->work_total = gmdate('H:i', max(0, $workSeconds));
            } else {
                $attendance->work_total = '00:00';
            }
        }

        // ビューで使いやすいようにuser_idでキー付け
        $attendances = $attendances->keyBy('user_id');

        return view('admin.attendance.list', compact('date', 'prevDate', 'nextDate', 'staffs', 'attendances'));
    }

    public function show(Request $request, $id)
    {
        // 修正ポイント：「0」なら新規作成、「0以外」なら既存データ検索
        if ($id == 0) {
            // --- 新規作成モード ---
            $date = $request->query('date') ?? date('Y-m-d'); // 日付がない場合は今日
            $userId = $request->query('user_id');
        
            $attendance = new Attendance();
            $attendance->date = $date;
            $attendance->user_id = $userId;
            // ユーザー情報を取得
            $attendance->user = User::findOrFail($userId);
        
        } else {
            // --- 既存データ修正モード ---
            // ここで初めて findOrFail を使います
            $attendance = Attendance::with(['user', 'restTimes'])->findOrFail($id);
        }
    
        $target = $attendance;
        return view('admin.attendance.show', compact('attendance', 'target'));
    }

    public function update(UpdateAttendanceRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);

        // バリデーション済みのデータを使って更新
        $attendance->start_time = $attendance->date . ' ' . $request->input('attendance.0.start_time') . ':00';
        $attendance->end_time   = $attendance->date . ' ' . $request->input('attendance.0.end_time') . ':00';
        $attendance->remarks    = $request->remarks;
    
        $attendance->save();

        // 休憩時間の保存
        $this->saveRestTimes($attendance, $request);

        return redirect()->route('admin.attendance.list', ['date' => $attendance->date])
                    ->with('message', '勤怠を修正しました');
    }

    public function store(Request $request)
    {
        // 1. バリデーション（要件FN039準拠）
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'attendance.0.start_time' => 'required',
            'attendance.0.end_time'   => 'required|after:attendance.0.start_time',
            'remarks'                 => 'required',
        ], [
            'attendance.0.end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'remarks.required'           => '備考を記入してください',
        ]);

        // 2. 勤怠レコードの作成
        $attendance = Attendance::create([
            'user_id'    => $request->user_id,
            'date'       => $request->date, // 送信されてきた日付を使用
            'start_time' => $request->date . ' ' . $request->input('attendance.0.start_time') . ':00',
            'end_time'   => $request->date . ' ' . $request->input('attendance.0.end_time') . ':00',
            'remarks'    => $request->remarks,
        ]);

        // 3. 休憩時間の保存
        $this->saveRestTimes($attendance, $request);

        // 4. 一覧画面へ戻る
        return redirect()->route('admin.attendance.list', ['date' => $request->date])
                        ->with('message', '勤怠を登録しました');
    }

    // 掃除役のヘルパーメソッド（同じクラス内に記述）
    private function saveRestTimes($attendance, $request)
    {
        $attendance->restTimes()->delete();

        for ($i = 1; $i <= 2; $i++) {
            $rest = $request->input("attendance.{$i}");
        
            // 休憩時間が入力されているかチェック
            if (!empty($rest['start_time']) && !empty($rest['end_time'])) {
                // ★ポイント：日付を結合して完全な日時形式にする
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
            // with('restTimes') を追加して事前に取得しておく
            $record = Attendance::with('restTimes')
                                ->where('user_id', $id)
                                ->whereDate('date', $date)
                                ->first();

            $formattedDate = $date->format('m/d') . '(' . $weekMap[$date->dayOfWeek] . ')';

            // 休憩時間の合計を計算
            $restTotalSeconds = 0;
            if ($record) {
                $restTotalSeconds = $record->restTimes->sum(function($rest) {
                    return Carbon::parse($rest->start_time)->diffInSeconds(Carbon::parse($rest->end_time));
                });
            }
            $total_rest = $restTotalSeconds > 0 ? gmdate('H:i', $restTotalSeconds) : '';

            // 合計労働時間の計算
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
                'total_rest' => $total_rest, // 計算した変数を使用
                'total_work' => $total_work, // 計算した変数を使用
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
            // 日本語文字化け防止のためのBOM出力
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSVのヘッダー
            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            // データ出力
            foreach ($attendances as $attendance) {
                fputcsv($handle, [
                    $attendance->date,
                    $attendance->start_time,
                    $attendance->end_time,
                    $attendance->rest_time, // モデルのアクセサ
                    $attendance->work_time, // モデルのアクセサ
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
        // タブのデフォルトは 'waiting'
        $tab = $request->query('tab', 'waiting');
    
        // status 0: 承認待ち, 1: 承認済み
        $status = ($tab === 'approved') ? 1 : 0;

        // ページネーションで取得（withでリレーションを読み込み高速化）
        $requests = StampCorrectionRequest::where('status', $status)
            ->with(['user', 'attendance']) // 名前と日付を取得するために必要
            ->latest()
            ->paginate(10);

        return view('admin.request.list', compact('requests', 'tab'));
    }

    public function approveRequest($id)
    {
        // 承認済みでも承認待ちでも、その申請ID(req_id)でデータを取得する
        $req = \App\Models\StampCorrectionRequest::with(['user', 'attendance'])->findOrFail($id);
    
        // 申請から勤怠データを確実に取得
        $attendance = $req->attendance;
        if (!$attendance) {
            abort(404);
        }

        $isApprovalMode = true; 

        // ここで $req と $attendance を両方渡す
        return view('admin.request.approve', compact('req', 'attendance', 'isApprovalMode'));
    }

    public function updateRequest(Request $request, $id)
    {
        // 1. 申請データを取得
        $req = \App\Models\StampCorrectionRequest::findOrFail($id);

        // 2. 申請対象の勤怠データを取得
        $attendance = $req->attendance;

        // 3. トランザクションを使って安全に更新
        \DB::transaction(function () use ($req, $attendance) {
            // ① 勤怠データを申請内容で更新
            $attendance->update([
                'start_time' => $req->start_time,
                'end_time'   => $req->end_time,
            ]);

            // ② 既存の休憩時間を全削除して、申請内容から再登録
            \App\Models\RestTime::where('attendance_id', $attendance->id)->delete();

            // 申請からJSONデータをデコード
            $restData = json_decode($req->rest_data, true);
        
            if ($restData) {
                foreach ($restData as $rest) {
                    // 開始・終了があるものだけ登録
                    if (!empty($rest['start_time']) && !empty($rest['end_time'])) {
                        \App\Models\RestTime::create([
                            'attendance_id' => $attendance->id,
                            'start_time'    => $rest['start_time'],
                            'end_time'      => $rest['end_time'],
                        ]);
                    }
                }
            }

            // ③ 申請ステータスを「承認済み(1)」に変更
            $req->update(['status' => 1]);
        });

        // 4. 一覧画面へ戻る（成功メッセージを添えて）
        return redirect()->route('admin.request.list')->with('success', '承認が完了しました');
    }
}