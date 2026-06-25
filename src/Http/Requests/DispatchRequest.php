<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Xjoc\NotificationCenter\Models\NotificationType;
use Xjoc\NotificationCenter\Support\RecipientResolver;

final class DispatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Closure|In|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'recipients' => ['required', 'array'],
            'recipients.model' => ['required', 'string', $this->allowedModelRule()],
            'recipients.ids' => ['required', 'array'],
            'recipients.ids.*' => ['required'],
            'variables' => ['sometimes', 'array'],
            'channels' => ['sometimes', 'array'],
            'channels.*' => ['string', Rule::in($this->supportedChannels())],
        ];
    }

    private function allowedModelRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value)) {
                $fail('The recipient model must be a string.');

                return;
            }

            if (! $this->resolver()->allows($value)) {
                $fail("The recipient model [{$value}] is not an allowed notifiable.");
            }
        };
    }

    private function resolver(): RecipientResolver
    {
        /** @var RecipientResolver $resolver */
        $resolver = app(RecipientResolver::class);

        return $resolver;
    }

    /**
     * @return array<int, string>
     */
    private function supportedChannels(): array
    {
        $type = $this->route('type');

        if (! $type instanceof NotificationType) {
            return [];
        }

        return $type->supported_channels;
    }
}
