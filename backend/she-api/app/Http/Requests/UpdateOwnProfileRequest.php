<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOwnProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (['name', 'username', 'phone', 'position'] as $field) {
            if ($this->has($field)) {
                $values[$field] = filled($this->input($field))
                    ? trim((string) $this->input($field))
                    : null;
            }
        }

        $this->merge($values);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['nullable', 'string', 'max:255'],
            'current_password' => ['nullable', 'required_with:password', 'string'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'signature' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => ':attribute wajib diisi.',
            '*.max' => ':attribute melebihi batas maksimum :max karakter.',
            'username.unique' => 'Username sudah digunakan.',
            'current_password.required_with' => 'Password saat ini wajib diisi untuk mengganti password.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.',
            'signature.image' => 'Tanda tangan harus berupa gambar.',
            'signature.mimes' => 'Tanda tangan harus berformat JPG, JPEG, PNG, atau WEBP.',
            'signature.max' => 'Ukuran tanda tangan maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'Nama',
            'username' => 'Username',
            'phone' => 'No. Telepon',
            'position' => 'Jabatan',
            'current_password' => 'Password saat ini',
            'password' => 'Password baru',
            'signature' => 'Tanda tangan',
        ];
    }
}
