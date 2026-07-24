<?php

namespace App\Http\Requests;

class UpdateSafetyTalkTrainingRequest extends StoreSafetyTalkTrainingRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['activity_photo'] = ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:5120'];

        return $rules;
    }
}
