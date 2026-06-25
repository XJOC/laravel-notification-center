<?php

declare(strict_types=1);

namespace XJOC\NotificationCenter\Templates;

use Illuminate\Support\Arr;
use XJOC\NotificationCenter\Exceptions\MissingVariableException;

final class TemplateRenderer
{
    /**
     * Render a template string by replacing raw `{!! key !!}` and escaped
     * `{{ key }}` tokens with values resolved from $variables.
     *
     * @param  array<string, mixed>  $variables
     */
    public function render(string $template, array $variables, bool $escape = true, string $onMissing = 'empty'): string
    {
        $withRaw = preg_replace_callback(
            '/\{!!\s*([\w.]+)\s*!!\}/',
            fn (array $matches): string => $this->resolve($matches[1], $variables, false, $onMissing),
            $template,
        ) ?? $template;

        return preg_replace_callback(
            '/\{\{\s*([\w.]+)\s*\}\}/',
            fn (array $matches): string => $this->resolve($matches[1], $variables, $escape, $onMissing),
            $withRaw,
        ) ?? $withRaw;
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    private function resolve(string $key, array $variables, bool $escape, string $onMissing): string
    {
        if (! Arr::has($variables, $key)) {
            if ($onMissing === 'throw') {
                throw MissingVariableException::forKey($key);
            }

            return '';
        }

        $value = $this->stringify(Arr::get($variables, $key));

        return $escape ? e($value) : $value;
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return (string) json_encode($value);
        }

        return '';
    }
}
