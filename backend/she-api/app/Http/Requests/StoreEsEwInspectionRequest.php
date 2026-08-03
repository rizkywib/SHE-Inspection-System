<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEsEwInspectionRequest extends FormRequest
{
    public const CONDITION_FIELDS = [
        'water_flow_es',
        'water_flow_ew',
        'water_condition',
        'actual_valve_es',
        'actual_valve_ew',
        'physical_condition_es',
        'physical_condition_ew',
        'sign_board_condition',
        'housekeeping_condition',
        'road_access_condition',
        'sewer_condition',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = collect($this->input('items', []))->map(function ($item) {
            foreach (['remark'] as $field) {
                if (isset($item[$field]) && is_string($item[$field])) {
                    $item[$field] = filled($item[$field]) ? trim($item[$field]) : null;
                }
            }

            return $item;
        })->all();

        $this->merge([
            'reference_no' => filled($this->reference_no) ? trim((string) $this->reference_no) : null,
            'notes' => filled($this->notes) ? trim((string) $this->notes) : null,
            'items' => $items,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'reference_no' => ['nullable', 'string', 'max:40', 'unique:es_ew_inspections,reference_no'],
            'inspection_date' => ['required', 'date'],
            'area_id' => ['required', 'integer', Rule::exists('es_ew_areas', 'id')],
            'inspector_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'point_id' => ['required', 'integer', Rule::exists('point', 'id')->where('status', 1)],
            'items' => ['required', 'array', 'size:1'],
            'items.*.remark' => ['nullable', 'string', 'max:5000'],
            'items.*.photo_before' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'items.*.photo_after' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ];

        foreach (self::CONDITION_FIELDS as $field) {
            $rules["items.*.{$field}"] = ['required', 'boolean'];
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
            'items.min' => 'Minimal satu item ES/EW harus diisi.',
            'items.size' => 'Setiap inspeksi ES/EW hanya boleh memiliki satu item.',
            'items.*.photo_before.mimes' => 'Foto sebelum harus berformat JPG, JPEG, PNG, atau WEBP.',
            'items.*.photo_after.mimes' => 'Foto sesudah harus berformat JPG, JPEG, PNG, atau WEBP.',
            'items.*.photo_before.max' => 'Ukuran foto sebelum maksimal 10 MB.',
            'items.*.photo_after.max' => 'Ukuran foto sesudah maksimal 10 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'inspection_date' => 'Tanggal Inspeksi',
            'area_id' => 'Area',
            'inspector_id' => 'Inspector',
            'point_id' => 'Name',
            'items' => 'Item ES/EW',
        ];
    }
}
