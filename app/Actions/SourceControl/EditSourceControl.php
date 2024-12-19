<?php

namespace App\Actions\SourceControl;

use App\Models\SourceControl;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EditSourceControl
{
    public function edit(SourceControl $sourceControl, User $user, array $input): void
    {
        $sourceControl->profile = $input['name'];
        $sourceControl->url = $input['url'] ?? null;
        $sourceControl->project_id = isset($input['global']) && $input['global'] ? null : $user->current_project_id;

        $sourceControl->provider_data = $sourceControl->provider()->createData($input);

        if (! $sourceControl->provider()->connect()) {
            throw ValidationException::withMessages([
                'token' => __('Cannot connect to :provider or invalid token!', ['provider' => $sourceControl->provider]
                ),
            ]);
        }

        $sourceControl->save();
    }

    public static function rules(array $input): array
    {
        $rules = [
            'name' => [
                'required',
            ],
            'provider' => [
                'required',
                Rule::in(config('core.source_control_providers')),
            ],
        ];

        return array_merge($rules, static::providerRules($input));
    }

    /**
     * @throws ValidationException
     */
    private static function providerRules(array $input): array
    {
        if (! isset($input['provider'])) {
            return [];
        }

        $sourceControl = new SourceControl([
            'provider' => $input['provider'],
        ]);

        return $sourceControl->provider()->createRules($input);
    }
}
