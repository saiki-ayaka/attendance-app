<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StampCorrectionRequest;
use Illuminate\Support\Facades\Auth;

class StampCorrectionRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'waiting');
        $status = ($tab === 'approved') ? 1 : 0;

        $requests = StampCorrectionRequest::where('user_id', Auth::id())
                        ->where('status', $status)
                        ->with(['attendance', 'user']) // 必須！
                        ->latest()           // 新しい順に並べる
                        ->paginate(10); 

        return view('stamp_correction.list', compact('requests', 'tab'));
    }
}
