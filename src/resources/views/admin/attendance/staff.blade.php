@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-staff.css') }}">
@endsection

@section('content')
{{-- 画面全体を覆うコンテナ（グレー背景用） --}}
<div class="attendance-page-wrapper">

    {{-- 1. タイトルエリア（白カードの外なので、グレー背景の上に直接載ります） --}}
    <h2 class="attendance-title">{{ $staff->name }}さんの勤怠</h2>

    {{-- 2. 月選択セレクターカード（独立した白背景） --}}
    <div class="month-selector-card">
        {{-- 前月へのリンク --}}
        <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $prev_month]) }}" class="month-nav">← 前月</a>
        
        <div class="current-month">
            <img src="{{ asset('img/calendar-icon.png') }}" alt="" class="calendar-icon">
            {{-- 現在表示中の月 --}}
            <span>{{ $display_month }}</span>
        </div>
        
        {{-- 翌月へのリンク --}}
        <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $next_month]) }}" class="month-nav">翌月 →</a>
    </div>

    {{-- 3. テーブル＆ボタンカード（独立した白背景） --}}
    <div class="attendance-table-card">
        {{-- 勤怠テーブル --}}
        <div class="attendance-table-wrapper">
            <table class="attendance-table">
                <thead>
                    <tr>
                        <th>日付</th>
                        <th>出勤</th>
                        <th>退勤</th>
                        <th>休憩</th>
                        <th>合計</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attendances as $attendance)
                    <tr>
                        <td>{{ $attendance['date'] }}</td>
                        <td>{{ $attendance['start_time'] }}</td>
                        <td>{{ $attendance['end_time'] }}</td>
                        <td>{{ $attendance['total_rest'] ?? '' }}</td>
                        <td>{{ $attendance['total_work'] ?? '' }}</td>
                        <td>
                            @if(is_numeric($attendance['id']))
                                {{-- 既存データがある場合：そのIDで詳細画面へ --}}
                                <a href="{{ route('admin.attendance.show', ['id' => $attendance['id']]) }}" class="detail-link">詳細</a>
                            @else
                                {{-- データがない場合：IDは必ず0にし、日付とuser_idをクエリパラメータとして渡す --}}
                                <a href="{{ route('admin.attendance.show', [
                                    'id' => 0, 
                                    'user_id' => $staff->id, 
                                    'date' => $attendance['id']  {{-- ここが日付文字列です --}}
                                ]) }}" class="detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

        {{-- CSV出力ボタン --}}
    <div class="csv-export-area">
        <a href="{{ route('admin.staff.export', ['id' => $staff->id, 'month' => request('month')]) }}" class="csv-button">CSV出力</a>
    </div>

</div>
@endsection