<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Xjoc\NotificationCenter\Channels\ChannelRegistry;
use Xjoc\NotificationCenter\Models\NotificationType;

final class UpdateTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, In|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'supported_channels' => ['sometimes', 'array'],
            'supported_channels.*' => ['string', Rule::in($this->configuredChannels())],
            'is_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('is_enabled') && $this->has('is_enabled')) {
                $this->guardDisable($validator);
            }
        });
    }

    private function guardDisable(Validator $validator): void
    {
        $type = $this->route('type');

        if (! $type instanceof NotificationType) {
            return;
        }

        if ($type->category->bypassesGateway()) {
            $validator->errors()->add('is_enabled', 'Essential notifications cannot be disabled.');

            return;
        }

        if ($type->is_locked) {
            $validator->errors()->add('is_enabled', 'Locked notifications cannot be disabled.');
        }
    }

    /**
     * The registered channel keys — the only channels an admin may assign.
     *
     * @return array<int, string>
     */
    private function configuredChannels(): array
    {
        return app(ChannelRegistry::class)->keys();
    }
}
