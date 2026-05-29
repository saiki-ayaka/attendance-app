@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendance.css') }}">
@endsection

@section('content')
<div class="attendance__content">
    
    {{-- 1. ステータス表示 --}}
    <div class="attendance__status">
        <span class="status-label">
            @if ($status === 'attendance_none') 勤務外
            @elseif ($status === 'working') 出勤中
            @elseif ($status === 'resting') 休憩中
            @elseif ($status === 'attendance_end') 退勤済
            @endif
        </span>
    </div>

    {{-- 2. 日付・時刻表示（Laravelから渡された変数をそのまま表示） --}}
    <div class="attendance__panel">
        <p class="attendance__date">{{ $today }}</p>
        <p class="attendance__time">{{ $time }}</p>
    </div>

    {{-- 3. ボタンエリア（要件に基づく出し分け） --}}
    <div class="attendance__button-group">
        @if ($status === 'attendance_none')
            {{-- 勤務外：出勤ボタンのみを表示 --}}
            <form action="/attendance/work-start" method="post">
                @csrf
                <button class="attendance__button-submit" type="submit">出勤</button>
            </form>

        @elseif ($status === 'working')
            {{-- 出勤中：退勤ボタンと「休憩」ボタンを表示 --}}
            <form action="/attendance/work-end" method="post">
                @csrf
                <button class="attendance__button-submit" type="submit">退勤</button>
            </form>
            <form action="/attendance/rest-start" method="post">
                @csrf
                <button class="attendance__button-sub" type="submit">休憩</button>
            </form>

        @elseif ($status === 'resting')
            {{-- 休憩中：休憩戻ボタンのみを表示 --}}
            <form action="/attendance/rest-end" method="post">
                @csrf
                <button class="attendance__button-submit" type="submit">休憩戻</button>
            </form>

        @elseif ($status === 'attendance_end')
            {{-- 退勤済：メッセージを表示 --}}
            @if(session('attendance_message'))
                <p class="attendance__thanks">{{ session('attendance_message') }}</p>
            @else
                <p class="attendance__thanks">お疲れ様でした。</p>
            @endif
        @endif
    </div>
</div>
@endsection