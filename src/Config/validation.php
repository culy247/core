<?php

return [
    'customer'   => [
        'first_name'         => 'required|string|max:100',
        'password'           => 'required|string|min:6',
        'password_null'      => 'nullable|string|min:6',
        'password_confirm'   => 'required|string|min:6|confirmed',
        'email'              => 'required|string|email|max:255',
        'last_name_required' => 'required|string|max:100',
        'last_name_null'     => 'nullable|string|max:100',
        'address1_required'  => 'required|string|max:100',
        'address1_null'      => 'nullable|string|max:100',
        'address2_required'  => 'required|string|max:100',
        'address2_null'      => 'nullable|string|max:100',
        'phone_required'     => 'required|regex:/^0[^0][0-9\-]{7,13}$/',
        'phone_null'         => 'nullable|regex:/^0[^0][0-9\-]{7,13}$/',
        'country_required'   => 'required|string|min:2',
        'country_null'       => 'nullable|string|min:2',
        'postcode_required'  => 'required|min:5',
        'postcode_null'      => 'nullable|min:5',
        'company_required'   => 'required|string|max:100',
        'company_null'       => 'nullable|string|max:100',
        'sex_required'       => 'required|integer|max:10',
        'sex_null'           => 'nullable|integer|max:10',
        'birthday_required'  => 'required|date|date_format:Y-m-d',
        'birthday_null'      => 'nullable|date|date_format:Y-m-d',
        'group_required'     => 'required|integer|max:10',
        'group_null'         => 'nullable|integer|max:10',
        'name_kana_required' => 'required|string|max:100',
        'name_kana_null'     => 'nullable|string|max:100',
    ], 
];
