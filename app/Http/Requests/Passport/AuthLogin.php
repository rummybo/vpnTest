<?php

namespace App\Http\Requests\Passport;

use Illuminate\Foundation\Http\FormRequest;

class AuthLogin extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'sometimes|email:strict',
            'username' => 'sometimes|alpha_dash|min:3|max:20',
            'phone' => 'sometimes|regex:/^1[3-9]\d{9}$/',
            'password' => 'required|min:8'
        ];
    }

    public function messages()
    {
        return [
            'email.required' => __('Email can not be empty'),
            'email.email' => __('Email format is incorrect'),
            'username.alpha_dash' => __('Username can only contain letters, numbers, dashes and underscores'),
            'username.min' => __('Username must be at least 3 characters'),
            'username.max' => __('Username cannot exceed 20 characters'),
            'phone.regex' => __('Phone format is incorrect'),
            'password.required' => __('Password can not be empty'),
            'password.min' => __('Password must be greater than 8 digits')
        ];
    }
}
