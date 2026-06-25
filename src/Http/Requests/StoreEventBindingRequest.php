<?php

declare(strict_types=1);

namespace Xjoc\NotificationCenter\Http\Requests;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class StoreEventBindingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'event_class' => ['required', 'string', $this->classExistsRule()],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    private function classExistsRule(): Closure
    {
        return static function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! class_exists($value)) {
                $fail('The given event class does not exist.');
            }
        };
    }
}
