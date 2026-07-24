<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateSafeWorkPermitInspectionRequest extends StoreSafeWorkPermitInspectionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $inspectionId = $this->route('safe_work_permit_inspection') ?? $this->route('id');
        $rules['permit_number'] = [
            'required',
            'string',
            'max:255',
            Rule::unique('safe_work_permit_inspections', 'permit_number')->ignore($inspectionId),
        ];

        return $rules;
    }
}
