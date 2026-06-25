<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Xjoc\NotificationCenter\Models\NotificationType;

final class UpsertTemplateRequest extends FormRequest
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
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $type = $this->route('type');

            if (! $type instanceof NotificationType) {
                return;
            }

            if (! in_array($this->validatedChannel(), $type->supported_channels, true)) {
                $validator->errors()->add('channel', 'The given channel is not supported by this notification type.');
            }
        });
    }

    public function validatedChannel(): string
    {
        $channel = $this->route('channel');

        return is_string($channel) ? $channel : '';
    }
}
