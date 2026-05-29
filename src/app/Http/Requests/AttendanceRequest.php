<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceRequest extends FormRequest
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

    protected function prepareForValidation()
    {
        $data = $this->all();
        if (isset($data['attendance'])) {
            foreach ($data['attendance'] as $index => &$times) {
                foreach ($times as $key => &$value) {
                    if ($value) {
                        // 全角数字を半角に、全角コロンを半角コロンに変換
                        $value = mb_convert_kana($value, 'n');
                        $value = str_replace('：', ':', $value);
                    }
                }
            }
            $this->replace($data);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'attendance.0.start_time' => 'required',
            'attendance.0.end_time'   => ['required',  'after:attendance.0.start_time'],
            'attendance.1.start_time' => ['nullable', 'after_or_equal:attendance.0.start_time'],
            'attendance.1.end_time'   => ['nullable', 'before_or_equal:attendance.0.end_time', 'after:attendance.1.start_time'],
            'attendance.2.start_time' => ['nullable', 'after_or_equal:attendance.1.end_time'],
            'attendance.2.end_time'   => ['nullable', 'before_or_equal:attendance.0.end_time', 'after:attendance.2.start_time'],
            'remarks'                 => ['required', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'attendance.0.start_time.required'       => '出勤時刻を入力してください',
            'attendance.0.end_time.required'         => '退勤時刻を入力してください',
            'attendance.0.end_time.after'            => '出勤時間もしくは退勤時間が不適切な値です',
            'attendance.1.start_time.after_or_equal' => '休憩時間が不適切な値です',
            'attendance.1.end_time.before_or_equal'  => '休憩時間が不適切な値です',
            'attendance.1.end_time.after'            => '休憩時間が不適切な値です',
            'attendance.2.start_time.after_or_equal' => '休憩時間が不適切な値です',
            'attendance.2.end_time.before_or_equal'  => '休憩時間が不適切な値です',
            'attendance.2.end_time.after'            => '休憩時間が不適切な値です',
            'remarks.required'                       => '備考を記入してください',
        ];
    }
}
