<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSafetyTalkTrainingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->topic)) {
            $this->merge(['topic' => trim($this->topic)]);
        }
    }

    public function rules(): array
    {
        return [
            'speaker_id' => [
                'required',
                'integer',
                Rule::exists('safety_talk_speakers', 'id')->where('is_active', true),
            ],
            'implementation_date' => ['required', 'date'],
            'topic' => ['required', 'string', 'max:1000'],
            'ecogreen_participants' => ['required', 'integer', 'min:0'],
            'outsourcing_participants' => ['required', 'integer', 'min:0'],
            'contractor_participants' => ['required', 'integer', 'min:0'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'implementation_area' => ['required', Rule::in([1, 2, 3, 4, 5, 6])],
            'activity_photo' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.required' => ':attribute wajib diisi.',
            '*.integer' => ':attribute harus berupa angka bulat.',
            '*.min' => ':attribute minimal :min.',
            '*.max' => ':attribute melebihi batas maksimum :max.',
            '*.exists' => ':attribute tidak valid atau sudah tidak aktif.',
            'implementation_date.date' => 'Tanggal Pelaksanaan harus berupa tanggal yang valid.',
            'implementation_area.in' => 'Area Pelaksanaan harus antara Area 1 sampai Area 6.',
            'activity_photo.image' => 'Foto Penyampaian harus berupa gambar.',
            'activity_photo.mimes' => 'Foto Penyampaian harus berformat JPG, JPEG, atau PNG.',
            'activity_photo.max' => 'Ukuran Foto Penyampaian maksimal 5 MB.',
        ];
    }

    public function attributes(): array
    {
        return [
            'speaker_id' => 'Pembicara',
            'implementation_date' => 'Tanggal Pelaksanaan',
            'topic' => 'Topik / Materi',
            'ecogreen_participants' => 'Peserta Ecogreen',
            'outsourcing_participants' => 'Peserta Outsourcing',
            'contractor_participants' => 'Peserta Contractor',
            'duration_minutes' => 'Durasi',
            'implementation_area' => 'Area Pelaksanaan',
            'activity_photo' => 'Foto Penyampaian',
        ];
    }
}
