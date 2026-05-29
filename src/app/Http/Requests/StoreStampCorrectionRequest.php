<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStampCorrectionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'start_time' => 'required',
            'end_time'   => 'required|after:start_time',
            'remarks'    => 'required',
            // 休憩時間のバリデーションを配列形式で追加
            'attendance.1.start_time' => 'nullable|after_or_equal:start_time|before:end_time',
            'attendance.1.end_time'   => 'nullable|after:attendance.1.start_time|before_or_equal:end_time',
            'attendance.2.start_time' => 'nullable|after_or_equal:start_time|before:end_time',
            'attendance.2.end_time'   => 'nullable|after:attendance.2.start_time|before_or_equal:end_time',
        ];
    }

    public function messages()
    {
        return [
            'start_time.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.required'   => '出勤時間もしくは退勤時間が不適切な値です',
            'end_time.after'      => '出勤時間もしくは退勤時間が不適切な値です',
            'remarks.required'    => '備考を記入してください',
        
        // ★ワイルドカードを使って休憩の全てのルールを1行にまとめる！
            'attendance.*.start_time.after_or_equal' => '休憩時間が不適切な値です',
            'attendance.*.start_time.before'         => '休憩時間が不適切な値です',
            'attendance.*.end_time.after'            => '休憩時間もしくは退勤時間が不適切な値です',
            'attendance.*.end_time.before_or_equal'  => '休憩時間もしくは退勤時間が不適切な値です',
        ];
    }
}
