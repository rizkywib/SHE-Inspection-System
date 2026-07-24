<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSafeWorkPermitInspectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'permit_number' => is_string($this->permit_number) ? trim($this->permit_number) : $this->permit_number,
            'permit_findings' => filled($this->permit_findings) ? trim((string) $this->permit_findings) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'permit_date' => ['required', 'date'],
            'inspector_id' => ['required', 'integer', Rule::exists('permit_inspectors', 'id')->where('is_active', true)],
            'permit_number' => ['required', 'string', 'max:255', 'unique:safe_work_permit_inspections,permit_number'],
            'permit_type_id' => ['required', 'integer', Rule::exists('permit_types', 'id')->where('is_active', true)],
            'supervision_area_id' => ['required', 'integer', Rule::exists('supervision_areas', 'id')->where('is_active', true)],
            'main_area_id' => ['required', 'integer', Rule::exists('permit_main_areas', 'id')->where('is_active', true)],
            'sub_area_id' => [
                'required',
                'integer',
                Rule::exists('permit_sub_areas', 'id')->where(function ($query) {
                    $query->where('is_active', true)
                        ->where(function ($query) {
                            $query->whereNull('main_area_id')
                                ->orWhere('main_area_id', $this->input('main_area_id'));
                        });
                }),
            ],
            'section_equipment' => ['required', 'string', 'max:255'],
            'job_performance' => ['required', 'string', 'max:65535'],
            'authorized_craftman' => ['required', 'string', 'max:255'],
            'authorized_facility' => ['required', 'string', 'max:255'],
            'contractor_name' => ['required', 'string', 'max:255'],
            'work_description' => ['required', 'string', 'max:65535'],
            'permit_findings' => ['nullable', 'string', 'max:65535'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => ':attribute wajib diisi.',
            '*.exists' => ':attribute tidak valid atau sudah tidak aktif.',
            '*.max' => ':attribute melebihi batas maksimum :max karakter.',
            'permit_date.date' => 'Tanggal Permit harus berupa tanggal yang valid.',
            'permit_number.unique' => 'No. Permit sudah digunakan.',
        ];
    }

    public function attributes(): array
    {
        return [
            'permit_date' => 'Tanggal Permit',
            'inspector_id' => 'Nama Inspector',
            'permit_number' => 'No. Permit',
            'permit_type_id' => 'Type Permit',
            'supervision_area_id' => 'Area Pengawasan',
            'main_area_id' => 'Main Area',
            'sub_area_id' => 'Sub Area',
            'section_equipment' => 'Section / Equipment',
            'job_performance' => 'Job Performance',
            'authorized_craftman' => 'Authorized Craftman',
            'authorized_facility' => 'Authorized Facility',
            'contractor_name' => 'Nama Kontraktor',
            'work_description' => 'Uraian Pekerjaan',
            'permit_findings' => 'Temuan Terkait Safe Work Permit',
        ];
    }
}
