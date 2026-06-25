<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Xjoc\NotificationCenter\Models\NotificationType;

final class UpdatePreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'opted_out' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->route('type');

            if (! $type instanceof NotificationType) {
                return;
            }

            $channel = $this->route('channel');
            $channel = is_string($channel) ? $channel : '';

            if (! in_array($channel, $type->supported_channels, true)) {
                $validator->errors()->add('channel', 'The given channel is not supported by this notification type.');
            }
        });
    }
}
