@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance-staff.css') }}">
@endsection

@section('content')
<div class="attendance-page-wrapper">
    <h2 class="attendance-title">{{ $staff->name }}さんの勤怠</h2>
    <div class="month-selector-card">
        <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $prev_month]) }}" class="month-nav">← 前月</a>
        <div class="current-month">
            <img src="{{ asset('img/calendar-icon.png') }}" alt="" class="calendar-icon">
            <span>{{ $display_month }}</span>
        </div>
        <a href="{{ route('admin.staff.attendance', ['id' => $staff->id, 'month' => $next_month]) }}" class="month-nav">翌月 →</a>
    </div>
    <div class="attendance-table-card">
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
                                <a href="{{ route('admin.attendance.show', ['id' => $attendance['id']]) }}" class="detail-link">詳細</a>
                            @else
                                <a href="{{ route('admin.attendance.show', [
                                    'id' => 0,
                                    'user_id' => $staff->id,
                                    'date' => $attendance['id']
                                ]) }}" class="detail-link">詳細</a>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="csv-export-area">
        <a href="{{ route('admin.staff.export', ['id' => $staff->id, 'month' => request('month')]) }}" class="csv-button">CSV出力</a>
    </div>
</div>
@endsection