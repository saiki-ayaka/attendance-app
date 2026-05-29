@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendancelist.css') }}">
@endsection

@section('content')
<div class="attendance-list__content">
    <div class="attendance-list__header">
        <h2 class="attendance-list__title">勤怠一覧</h2>
    </div>
    <div class="date-nav">
        <a class="date-nav__link" href="{{ route('attendance.list', ['month' => $prev_month]) }}">← 前月</a>
        <span class="date-nav__current">
            <img class="date-nav__icon" src="{{ asset('img/calendar-icon.png') }}" alt="カレンダー">
            {{ $display_month }}
        </span>
        <a class="date-nav__link" href="{{ route('attendance.list', ['month' => $next_month]) }}">翌月 →</a>
    </div>
    <div class="attendance-table__wrap">
        <table class="attendance-table">
            <thead>
                <tr class="attendance-table__row">
                    <th class="attendance-table__header">日付</th>
                    <th class="attendance-table__header">出勤</th>
                    <th class="attendance-table__header">退勤</th>
                    <th class="attendance-table__header">休憩</th>
                    <th class="attendance-table__header">合計</th>
                    <th class="attendance-table__header">詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attend)
                    <tr class="attendance-table__row">
                        <td class="attendance-table__item">{{ $attend->date }}</td>
                        <td class="attendance-table__item">{{ $attend->start_time }}</td>
                        <td class="attendance-table__item">{{ $attend->end_time }}</td>
                        <td class="attendance-table__item">{{ $attend->total_rest }}</td>
                        <td class="attendance-table__item">{{ $attend->total_work }}</td>
                        <td class="attendance-table__item">
                            <a class="detail-link" href="{{ route('attendance.show', ['id' => $attend->id ?? 'new', 'date' => $attend->raw_date]) }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection