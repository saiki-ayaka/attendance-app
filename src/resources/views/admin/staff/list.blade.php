@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/stafflist.css') }}">
@endsection

@section('content')
<div class="staff-page-wrapper">
    
    <h2 class="staff-title">スタッフ一覧</h2>

    <div class="staff-table-card">
        <div class="staff-table-wrapper">
            <table class="staff-table">
                <thead>
                    <tr>
                        <th>名前</th>
                        <th>メールアドレス</th>
                        <th>月次勤怠</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- 💡 コントローラーに合わせて $staffs に修正 --}}
                    @foreach($staffs as $staff)
                        <tr>
                            <td>{{ $staff->name }}</td>
                            <td>{{ $staff->email }}</td>
                            <td>
                                {{-- 名前付きルートを使ってスマートにリンク --}}
                                <a href="{{ route('admin.staff.attendance', $staff->id) }}" class="detail-link">詳細</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="pagination-wrapper">
        {{ $staffs->links('pagination::bootstrap-4') }}
    </div>
</div>
@endsection