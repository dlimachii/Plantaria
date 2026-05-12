<?php

namespace App\Http\Requests\Concerns;

trait SanitizesInput
{
    /**
     * @param array<string, callable(mixed): mixed> $sanitizers
     * @return array<string, mixed>
     */
    protected function sanitizedOnly(array $sanitizers): array
    {
        $sanitized = [];

        foreach ($sanitizers as $field => $sanitizer) {
            if (! $this->exists($field)) {
                continue;
            }

            $sanitized[$field] = $sanitizer($this->input($field));
        }

        return $sanitized;
    }

    protected function sanitizeText(mixed $value, bool $preserveNewLines = false): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $pattern = $preserveNewLines
            ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'
            : '/[\x00-\x1F\x7F]/u';

        $value = preg_replace($pattern, '', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function sanitizeHandle(mixed $value): ?string
    {
        $sanitized = $this->sanitizeText($value);

        return $sanitized === null ? null : strtolower($sanitized);
    }

    protected function sanitizeEmail(mixed $value): ?string
    {
        $sanitized = $this->sanitizeText($value);

        return $sanitized === null ? null : strtolower($sanitized);
    }
}
