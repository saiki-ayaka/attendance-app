@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendancelist.css') }}">
@endsection

@section('content')
<div class="admin-attendance__content">
    <div class="admin-attendance__header">
        <h2 class="admin-attendance__title">{{ \Carbon\Carbon::parse($date)->format('Y年n月j日') }}の勤怠</h2>
    </div>
    <div class="date-nav">
        <a class="date-nav__link" href="{{ route('admin.attendance.list', ['date' => $prevDate]) }}">← 前日</a>
        <span class="date-nav__current">
            <img class="calendar-icon" src="{{ asset('img/calendar-icon.png') }}" alt="カレンダー">
            {{ \Carbon\Carbon::parse($date)->format('Y/m/d') }}
        </span>
        <a class="date-nav__link" href="{{ route('admin.attendance.list', ['date' => $nextDate]) }}">翌日 →</a>
    </div>
    <div class="attendance-table__wrap">
        <table class="attendance-table">
            <tr class="attendance-table__row">
                <th class="attendance-table__header">名前</th>
                <th class="attendance-table__header">出勤</th>
                <th class="attendance-table__header">退勤</th>
                <th class="attendance-table__header">休憩</th>
                <th class="attendance-table__header">合計</th>
                <th class="attendance-table__header">詳細</th>
            </tr>
            @foreach($staffs as $staff)
                @php
                    $attendance = $attendances->get($staff->id);
                @endphp
                <tr class="attendance-table__row">
                    <td class="attendance-table__item">{{ $staff->name }}</td>
                    <td class="attendance-table__item">
                        {{ $attendance?->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance?->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance?->rest_total ?? '' }}
                    </td>
                    <td class="attendance-table__item">
                        {{ $attendance?->work_total ?? '' }}
                    </td>
                    <td class="attendance-table__item">
                        @if(isset($attendance) && $attendance->id)
                            <a class="detail-link" href="{{ route('admin.attendance.show', [
                                'id' => $attendance->id,
                                'user_id' => $attendance->user_id,
                                'date' => $attendance->date
                            ]) }}">詳細</a>
                        @else
                            <a class="detail-link" href="{{ route('admin.attendance.show', [
                                'id' => 0,
                                'user_id' => $staff->id,
                                'date' => $date
                            ]) }}">詳細</a>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $staffs->appends(['date' => $date])->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection