<?php

return [
    'custom' => [
        'category' => [
            'category_name' => [
                'required' => 'Tên danh mục là bắt buộc',
                'string' => 'Tên danh mục phải là chuỗi ký tự',
                'max' => 'Tên danh mục không được vượt quá 100 ký tự',
                'unique' => 'Tên danh mục đã tồn tại',
            ],
            'description' => [
                'string' => 'Mô tả phải là chuỗi ký tự',
                'max' => 'Mô tả không được vượt quá 255 ký tự',
            ],
            'invalid_data' => 'Dữ liệu không hợp lệ',
        ],
        'name' => [
            'string' => 'Tên phải là chuỗi ký tự.',
            'max' => 'Tên không được vượt quá 255 ký tự.',
        ],
        'user' => [
            'invalid_data' => 'Dữ liệu không hợp lệ',
            'name' => [
                'required' => 'Tên là bắt buộc',
                'string' => 'Tên phải là chuỗi ký tự',
                'max' => 'Tên không được vượt quá 255 ký tự',
            ],
            'email' => [
                'required' => 'Email là bắt buộc',
                'email' => 'Email không hợp lệ',
                'unique' => 'Email này đã được sử dụng',
            ],
            'phone' => [
                'required' => 'Số điện thoại là bắt buộc',
                'string' => 'Số điện thoại không hợp lệ',
                'unique' => 'Số điện thoại này đã được sử dụng',
                'max' => 'Số điện thoại không được vượt quá 50 ký tự',
            ],
            'password' => [
                'required' => 'Mật khẩu là bắt buộc',
                'min' => 'Mật khẩu phải có ít nhất 6 ký tự',
            ],
            'birthday' => [
                'date' => 'Ngày sinh không hợp lệ',
            ],
            'address' => [
                'max' => 'Địa chỉ không được vượt quá 256 ký tự',
            ],
        ],
        'phone' => [
            'string' => 'Số điện thoại phải là chuỗi ký tự.',
            'max' => 'Số điện thoại không được vượt quá 50 ký tự.',
            'unique' => 'Số điện thoại đã được sử dụng.',
        ],
        'email' => [
            'email' => 'Email không đúng định dạng.',
            'max' => 'Email không được vượt quá 255 ký tự.',
            'unique' => 'Email đã được sử dụng.',
        ],
        'birthday' => [
            'date' => 'Ngày sinh không đúng định dạng.',
            'before' => 'Ngày sinh phải là ngày trong quá khứ.',
        ],
        'address' => [
            'string' => 'Địa chỉ phải là chuỗi ký tự.',
            'max' => 'Địa chỉ không được vượt quá 256 ký tự.',
        ],
        'password' => [
            'min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'confirmed' => 'Xác nhận mật khẩu không khớp.',
        ],
    ],
];
