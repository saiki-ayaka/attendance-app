@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-container">
    <div class="request-header">
        <h2 class="request-title">申請一覧</h2>
    </div>

    {{-- タブ切り替え --}}
    <div class="request-tabs">
        <a href="{{ route('stamp_correction.index', ['tab' => 'waiting']) }}" 
           class="request-tab {{ $tab === 'waiting' ? 'request-tab--active' : '' }}">承認待ち</a>
        <a href="{{ route('stamp_correction.index', ['tab' => 'approved']) }}" 
           class="request-tab {{ $tab === 'approved' ? 'request-tab--active' : '' }}">承認済み</a>
    </div>

    <div class="request-table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>対象日時</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $request)
                <tr>
                    <td>
                        @if($request->status == 0)
                            <span class="status-badge status-badge--waiting">承認待ち</span>
                        @else
                            <span class="status-badge status-badge--approved">承認済み</span>
                        @endif
                    </td>
                    <td>{{ $request->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ Str::limit($request->remarks, 20) }}</td>
                    <td>{{ $request->created_at->format('Y/m/d') }}</td>
                    <td>
                        <a href="{{ route('attendance.show', $request->attendance_id) }}" class="detail-link">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
        {{ $requests->links() }}
    </div>
</div>
@endsection