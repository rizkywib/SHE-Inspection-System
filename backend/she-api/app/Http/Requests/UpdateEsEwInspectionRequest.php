<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class UpdateEsEwInspectionRequest extends StoreEsEwInspectionRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['reference_no'] = [
            'required',
            'string',
            'max:40',
            Rule::unique('es_ew_inspections', 'reference_no')->ignore($this->route('id')),
        ];

        return $rules;
    }
}
