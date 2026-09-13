<?php

namespace App\Http\Requests;

use App\Services\UserNotificationPreferenceService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateNotificationPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(UserNotificationPreferenceService $preferences): array
    {
        $types = array_keys($preferences->types());
        $channels = array_keys($preferences->channels());

        $rules = [
            'preferences' => ['required', 'array:'.implode(',', $types)],
        ];

        foreach ($types as $type) {
            $rules['preferences.'.$type] = ['sometimes', 'array:'.implode(',', $channels)];

            foreach ($channels as $channel) {
                $rules['preferences.'.$type.'.'.$channel] = ['sometimes', Rule::in(['0', '1', 0, 1, false, true])];
            }
        }

        return $rules;
    }
}
