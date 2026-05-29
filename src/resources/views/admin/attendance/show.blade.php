@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/attendanceshow.css') }}">
@endsection

@section('content')
<div class="attendance-detail__content">
    <h2 class="attendance-detail__title">勤怠詳細</h2>

    @if($attendance->id)
        <form action="{{ route('admin.attendance.update', $attendance->id) }}" method="post" novalidate>
            @method('PATCH')
    @else
            <form action="{{ route('admin.attendance.store') }}" method="post" novalidate>
    @endif
        @csrf
        {{-- dateやuser_idの隠しフィールドは必須 --}}
        <input type="hidden" name="date" value="{{ $attendance->date }}">
        <input type="hidden" name="user_id" value="{{ $attendance->user_id }}">

        <div class="attendance-detail__card">
            <table class="detail-table">
                <tr class="detail-table__row">
                    <th class="detail-table__header">名前</th>
                    <td class="detail-table__item"><span class="staff-name">{{ $attendance->user->name ?? '不明' }}</span></td>
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
                        <div class="time-input-group">
                            <input type="text" class="input-time-large" name="attendance[0][start_time]" value="{{ $attendance->start_time ? \Carbon\Carbon::parse($attendance->start_time)->format('H:i') : '' }}" required>
                            <span class="time-separator">～</span>
                            <input type="text" class="input-time-large" name="attendance[0][end_time]" value="{{ $attendance->end_time ? \Carbon\Carbon::parse($attendance->end_time)->format('H:i') : '' }}" required>
                        </div>
                        @error('attendance.0.start_time') <div class="error-msg">{{ $message }}</div> @enderror
                        @error('attendance.0.end_time') <div class="error-msg">{{ $message }}</div> @enderror
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">休憩</th>
                    <td class="detail-table__item">
                        @php $rest = $attendance->restTimes->get(0); @endphp
                        <div class="time-input-group">
                            <input type="text" class="input-time-large" name="attendance[1][start_time]" value="{{ $rest && $rest->start_time ? \Carbon\Carbon::parse($rest->start_time)->format('H:i') : '' }}" required>
                            <span class="time-separator">～</span>
                            <input type="text" class="input-time-large" name="attendance[1][end_time]" value="{{ $rest && $rest->end_time ? \Carbon\Carbon::parse($rest->end_time)->format('H:i') : '' }}" required>
                        </div>
                        @error('attendance.1.start_time') <div class="error-msg">{{ $message }}</div> @enderror
                        @error('attendance.1.end_time') <div class="error-msg">{{ $message }}</div> @enderror
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">休憩2</th>
                    <td class="detail-table__item">
                        @php $rest2 = $target->restTimes->get(1); @endphp
                        <div class="time-input-group">
                            <input type="text" class="input-time-large" name="attendance[2][start_time]" value="{{ $rest2 && $rest2->start_time ? \Carbon\Carbon::parse($rest2->start_time)->format('H:i') : '' }}">
                            <span class="time-separator">～</span>
                            <input type="text" class="input-time-large" name="attendance[2][end_time]" value="{{ $rest2 && $rest2->end_time ? \Carbon\Carbon::parse($rest2->end_time)->format('H:i') : '' }}">
                        </div>
                        @error('attendance.2.start_time') <div class="error-msg">{{ $message }}</div> @enderror
                        @error('attendance.2.end_time') <div class="error-msg">{{ $message }}</div> @enderror
                    </td>
                </tr>
                <tr class="detail-table__row">
                    <th class="detail-table__header">備考</th>
                    <td class="detail-table__item">
                        {{-- name="remarks" を name="reason" に変更 --}}
                        <textarea class="input-textarea-large" name="remarks" required>{{ old('remarks', $attendance->stampCorrectionRequest->remarks ?? $attendance->remarks) }}</textarea>
                        @error('remarks')
                            <div class="error-msg">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
            </table>
        </div>

        {{-- 要件FN038: 承認待ち等のチェック --}}
        @if($attendance->id && $attendance->status === 1)
            <div class="error-msg">*承認待ちのため修正はできません。</div>
        @else
            <div class="form__button-area">
            {{-- ここを固定で「修正」にする --}}
                <button class="button-submit-large" type="submit">修正</button>
            </div>
        @endif
    </form>
</div>
@endsection