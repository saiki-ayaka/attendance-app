<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LogoutResponse as LogoutResponseContract;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class LogoutResponse implements LogoutResponseContract
{
    public function toResponse($request)
    {
        if (Request::is('admin*')|| str_contains($request->header('referer'), 'admin')) {
            return redirect('/admin/login');
        }
        return redirect('/login');
    }
}