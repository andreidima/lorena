<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required',
            'name' => 'required|max:255',
            'telefon' => 'nullable|max:50',
            'email' => 'required|max:255|email:rfc,dns|unique:users,email,' . $this->route('user')?->id,
            'password' => ($this->isMethod('POST') ? 'required' : 'nullable') . '|min:8|max:255|confirmed',
            'activ' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Câmpul parolă este obligatoriu.',
            'password.min' => 'Parola trebuie să aibă minimum 8 caractere.',
            'password.max' => 'Câmpul parolă nu poate conține mai mult de 255 de caractere.',
        ];
    }
}
