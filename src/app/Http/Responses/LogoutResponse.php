<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        // 管理者画面からのログアウトなら /admin/login へ
        if (Request::is('admin*')|| str_contains($request->header('referer'), 'admin')) {
            return redirect('/admin/login');
        }

        // 一般ユーザーなら /login へ
        return redirect('/login');
    }
}