<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        // 役割が「一般ユーザー(role: 1)」のユーザーのみ取得
        $staffs = User::where('role', 1)->paginate(10);
        
        return view('admin.staff.list', compact('staffs'));
    }

    public function attendance($id)
    {
        // ここで対象スタッフの月次勤怠一覧を表示する処理を書きます（今は空でOK）
        return "スタッフ{$id}の月次勤怠画面へ";
    }
}
