<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize()
    {
        return true; // 管理者チェックが必要ならここで実装
    }

    protected function prepareForValidation()
    {
        $all = $this->all();
        
        $toHalf = function ($value) {
            return is_string($value) ? mb_convert_kana($value, 'n', 'UTF-8') : $value;
        };

        $newData = [
            'attendance' => array_map(function($item) use ($toHalf) {
                return [
                    'start_time' => $toHalf($item['start_time'] ?? ''),
                    'end_time'   => $toHalf($item['end_time'] ?? ''),
                ];
            }, $this->input('attendance', [])),
        ];

        $this->merge($newData);
    }

    public function rules()
    {
        return [
            'attendance.0.start_time' => 'required',
            'attendance.0.end_time'   => 'required|after:attendance.0.start_time',
            'remarks'                 => 'required|max:255',
            'attendance.1.end_time'   => 'nullable|after:attendance.1.start_time',
            'attendance.2.end_time'   => 'nullable|after:attendance.2.start_time',
        ];
    }

    public function messages()
    {
        return [
            'attendance.0.end_time.after' => '出勤時間もしくは退勤時間が不適切な値です',
            'attendance.1.end_time.after' => '休憩時間が不適切な値です',
            'attendance.2.end_time.after' => '休憩時間が不適切な値です',
            'remarks.required'            => '備考を記入してください',
            'remarks.max'                 => '備考は255文字以内で入力してください',
        ];
    }
}
