<?php

return [
    'required' => ':attribute wajib diisi.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'unique' => ':attribute sudah dipakai.',
    'string' => ':attribute harus berupa teks.',
    'min' => [
        'string' => ':attribute minimal :min karakter.',
    ],
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
    ],
    'attributes' => [
        'email' => 'email',
        'password' => 'password',
        'password_confirmation' => 'konfirmasi password',
        'username' => 'username',
        'form.email' => 'email',
        'form.password' => 'password',
    ],
];
