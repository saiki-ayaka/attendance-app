<?php

return [
    'required' => ':attribute を入力してください',
    'email'    => '有効なメールアドレス形式で入力してください',
    'unique'   => 'その :attribute は既に使用されています',
    'confirmed' => ':attribute が一致しません',
    // ログイン失敗時のメッセージ（これがあると便利です）
    'failed'   => 'メールアドレスまたはパスワードが正しくありません',

    'attributes' => [
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'name' => '名前',
        'attendance.0.start_time' => '出勤時間',
        'attendance.0.end_time'   => '退勤時間',
        'attendance.1.end_time'   => '休憩時間',
        'attendance.2.end_time'   => '休憩時間',
        'remarks'                 => '備考',
    ],
];
