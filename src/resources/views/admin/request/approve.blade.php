@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendance_fixed.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <h2 class="attendance-detail__title">勤怠詳細</h2>
    @php
        $isApprovalMode = $isApprovalMode ?? false;
        $start = ($req && $req->start_time) ? $req->start_time : $attendance->start_time;
        $end = ($req && $req->end_time) ? $req->end_time : $attendance->end_time;
        $remarks = ($req && $req->remarks) ? $req->remarks : $attendance->remarks;
    @endphp
    <form action="{{ $isApprovalMode ? route('admin.request.update', $req->id) : '' }}" method="post">
        @csrf
        @method('PATCH')
        <div class="attendance-detail__card">
            <div class="detail-table">
                <div class="detail-table__row">
                    <div class="detail-table__header">名前</div>
                    <div class="detail-table__item">{{ $attendance->user->name }}</div>
                </div>
                <div class="detail-table__row">
                    <div class="detail-table__header">日付</div>
                    <div class="detail-table__item">
                        {{ mb_convert_kana(\Carbon\Carbon::parse($attendance->date)->format('Y年'), 'A') }}
                        <span class="date-gap">{{ mb_convert_kana(\Carbon\Carbon::parse($attendance->date)->format('n月j日'), 'A') }}</span>
                    </div>
                </div>
                <div class="detail-table__row">
                    <div class="detail-table__header">出勤・退勤</div>
                    <div class="detail-table__item">
                        {{ mb_convert_kana(\Carbon\Carbon::parse($start)->format('H:i'), 'A') }}
                        <span class="time-separator-wide">～</span>
                        {{ mb_convert_kana(\Carbon\Carbon::parse($end)->format('H:i'), 'A') }}
                    </div>
                </div>
                <div class="detail-table__row">
                    <div class="detail-table__header">休憩1</div>
                    <div class="detail-table__item">
                        @if($attendance->rest_start_time1)
                            {{ mb_convert_kana(\Carbon\Carbon::parse($attendance->rest_start_time1)->format('H:i'), 'A') }}
                            <span class="time-separator-wide">～</span>
                            {{ mb_convert_kana(\Carbon\Carbon::parse($attendance->rest_end_time1)->format('H:i'), 'A') }}
                        @endif
                    </div>
                </div>
                <div class="detail-table__row">
                    <div class="detail-table__header">休憩2</div>
                    <div class="detail-table__item">
                        @if($attendance->rest_start_time2 && $attendance->rest_end_time2)
                            {{ mb_convert_kana(\Carbon\Carbon::parse($attendance->rest_start_time2)->format('H:i'), 'A') }}
                            <span class="time-separator-wide">～</span>
                            {{ mb_convert_kana(\Carbon\Carbon::parse($attendance->rest_end_time2)->format('H:i'), 'A') }}
                        @endif
                    </div>
                </div>
                <div class="detail-table__row">
                    <div class="detail-table__header">備考</div>
                    <div class="detail-table__item">{{ $remarks }}</div>
                </div>
            </div>
        </div>
        <div class="form__button-area">
            @if($isApprovalMode)
                @if($req->status == 0)
                    <button class="button-submit-large" type="submit">承認</button>
                @else
                    <button class="button-approved" type="button" disabled>承認済み</button>
                @endif
            @endif
        </div>
    </form>
</div>
@endsection