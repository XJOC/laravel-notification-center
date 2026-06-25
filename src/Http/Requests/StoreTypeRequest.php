<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Xjoc\NotificationCenter\Channels\ChannelRegistry;
use Xjoc\NotificationCenter\Enums\NotificationCategory;

final class StoreTypeRequest extends FormRequest
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
            'key' => ['required', 'string', 'unique:notification_types,key'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in($this->categoryValues())],
            'channels' => ['required', 'array'],
            'channels.*' => ['string', Rule::in($this->configuredChannels())],
            'locked' => ['sometimes', 'boolean'],
            'variables' => ['sometimes', 'array'],
            'variables.*' => ['string'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function categoryValues(): array
    {
        return array_map(
            static fn (NotificationCategory $category): string => $category->value,
            NotificationCategory::cases(),
        );
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
