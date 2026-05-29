@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/request.css') }}">
@endsection

@section('content')
<div class="request-container request-container--admin">
    <div class="request-header">
        <h2 class="request-title">申請一覧</h2>
    </div>
    <div class="request-tabs">
        <a href="{{ route('admin.request.list', ['tab' => 'waiting']) }}" class="request-tab {{ $tab === 'waiting' ? 'request-tab--active' : '' }}">承認待ち</a>
        <a href="{{ route('admin.request.list', ['tab' => 'approved']) }}" class="request-tab {{ $tab === 'approved' ? 'request-tab--active' : '' }}">承認済み</a>
    </div>
    <div class="request-table-wrapper">
        <table class="request-table">
            <thead>
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
            </thead>
            <tbody class="request-table__body">
                @foreach($requests as $request_item)
                <tr>
                    <td>
                        <span class="status-badge {{ $request_item->status == 1 ? 'status-badge--approved' : 'status-badge--waiting' }}">
                            {{ $request_item->status == 1 ? '承認済み' : '承認待ち' }}
                        </span>
                    </td>
                    <td>{{ $request_item->user->name }}</td>
                    <td>{{ \Carbon\Carbon::parse($request_item->attendance->date)->format('Y/m/d') }}</td>
                    <td>{{ Str::limit($request_item->remarks, 20) }}</td>
                    <td>{{ $request_item->created_at->format('Y/m/d') }}</td>
                    <td>
                            <a class="detail-link" href="{{ route('admin.attendance.approve.show', ['id' => $request_item->id]) }}">詳細</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pagination-wrapper">
    {{ $requests->appends(['tab' => $tab])->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection