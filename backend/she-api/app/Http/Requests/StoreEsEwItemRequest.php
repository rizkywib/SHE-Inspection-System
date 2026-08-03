<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEsEwItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remark' => filled($this->remark) ? trim((string) $this->remark) : null,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'point_id' => ['required', 'integer', Rule::exists('point', 'id')->where('status', 1)],
            'remark' => ['nullable', 'string', 'max:5000'],
            'photo_before' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'photo_after' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];

        foreach (StoreEsEwInspectionRequest::CONDITION_FIELDS as $field) {
            $rules[$field] = ['required', 'boolean'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            '*.required' => ':attribute wajib diisi.',
            '*.exists' => ':attribute tidak valid atau sudah tidak aktif.',
            '*.boolean' => ':attribute harus dipilih.',
            '*.max' => ':attribute melebihi batas maksimum :max.',
            'photo_before.mimes' => 'Foto Eye Wash harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photo_after.mimes' => 'Foto Emergency Shower harus berformat JPG, JPEG, PNG, atau WEBP.',
            'photo_before.max' => 'Ukuran foto Eye Wash maksimal 10 MB.',
            'photo_after.max' => 'Ukuran foto Emergency Shower maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'point_id' => 'Name',
            'photo_before' => 'Eye Wash',
            'photo_after' => 'Emergency Shower',
        ];
    }
}
