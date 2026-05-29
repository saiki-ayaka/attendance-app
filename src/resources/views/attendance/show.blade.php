@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/attendanceshow.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <h2 class="attendance-detail__title">勤怠詳細</h2>

    @php
        $target = $attendance;
        $attendanceId = $attendance->id ?? null;
        $req = $attendance->stampCorrectionRequest; // 申請データがあるか？
    
        // 承認済みは「申請があって、その承認statusが1（承認済み）」の場合
        $isApproved = ($req && $req->status == 1);
    
        // ★重要：承認待ちも「申請があって、その承認statusが0（承認待ち）」の場合のみにする
        $isWaiting = ($req && $req->status == 0);
    @endphp

    @if($attendanceId)
        <form action="{{ route('attendance.update', $attendanceId) }}" method="POST">
            @method('PUT')
    @else
        <form action="{{ route('attendance.store') }}" method="POST">
    @endif
        @csrf
        <input type="hidden" name="date" value="{{ $target->date ?? '' }}">
        <input type="hidden" name="user_id" value="{{ Auth::id() }}">

        <div class="attendance-detail__card">
            <table class="detail-table">
                <tr class="detail-table__row">
                    <th class="detail-table__header">名前</th>
                    <td class="detail-table__item">
                        <span class="staff-name">{{ $target->user->name ?? '不明' }}</span>
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">日付</th>
                    <td class="detail-table__item">
                        <span class="date-display">
                            <span class="year-text">{{ mb_convert_kana(\Carbon\Carbon::parse($target->date)->format('Y年'), 'A') }}</span>
                            {{ mb_convert_kana(\Carbon\Carbon::parse($target->date)->format('n月j日'), 'A') }}
                        </span>
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">出勤・退勤</th>
                    <td class="detail-table__item">
                        <input type="text" class="input-time-large" name="start_time" 
                            value="{{ old('start_time', $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                        <span class="time-separator">～</span>
                        <input type="text" class="input-time-large" name="end_time" 
                            value="{{ old('end_time', $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                        @if ($errors->has('start_time') || $errors->has('end_time'))
                            <div class="error-msg">出勤時間もしくは退勤時間が不適切な値です</div>
                        @endif
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">休憩</th>
                    <td class="detail-table__item">
                        @php $rest1 = $attendance->restTimes->get(0); @endphp
                        <input type="text" class="input-time-large" name="attendance[1][start_time]" 
                            value="{{ old('attendance.1.start_time', $rest1 ? \Carbon\Carbon::parse($rest1->start_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                            <span class="time-separator">～</span>
                        <input type="text" class="input-time-large" name="attendance[1][end_time]" 
                            value="{{ old('attendance.1.end_time', $rest1 ? \Carbon\Carbon::parse($rest1->end_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                        @if ($errors->has('attendance.1.start_time') || $errors->has('attendance.1.end_time'))
                            <div class="error-msg">休憩時間が不適切な値です</div>
                        @endif
                    </td>
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">休憩2</th>
                    <td class="detail-table__item">
                        @php $rest2 = $attendance->restTimes->get(1); @endphp
                        <input type="text" class="input-time-large" name="attendance[2][start_time]" 
                            value="{{ old('attendance.2.start_time', $rest2 && $rest2->start_time ? \Carbon\Carbon::parse($rest2->start_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                            <span class="time-separator">～</span>
                        <input type="text" class="input-time-large" name="attendance[2][end_time]" 
                            value="{{ old('attendance.2.end_time', $rest2 && $rest2->end_time ? \Carbon\Carbon::parse($rest2->end_time)->format('H:i') : '') }}" @if($isWaiting || $isApproved) readonly @endif>
                        @if ($errors->has('attendance.2.start_time') || $errors->has('attendance.2.end_time'))
                            <div class="error-msg">休憩時間が不適切な値です</div>
                        @endif
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">備考</th>
                    <td class="detail-table__item">
                        <textarea name="remarks" class="input-textarea-large" @if($isWaiting || $isApproved) readonly @endif>{{ old('remarks', $req->remarks ?? $attendance->remarks ?? '') }}</textarea>
                        @error('remarks')
                            <div class="error-msg">備考を記入してください</div>
                        @enderror
                    </td>
                </tr>
            </table>
        </div>

        <div class="form__button-area">
            @if($isApproved)
                <button class="button-approved" type="button" disabled>承認済み</button>
            @elseif($isWaiting)
                <p class="pending-warning-text">*承認待ちのため修正はできません。</p>
            @else
                <button type="submit" class="button-submit-large">修正</button>
            @endif
        </div>
    </form>
</div>
@endsection