<?php

namespace App\Support;

class Host
{
    /**
     * Extract host[:port] from a raw env value (URL, host, or host:port).
     */
    public static function fromUrlOrHost(?string $value, bool $includePort = true): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($value === '::1' || str_starts_with($value, '[::1]')) {
            return '::1';
        }

        if (! str_contains($value, '://')) {
            $value = 'https://'.$value;
        }

        $parts = parse_url($value);
        $host = $parts['host'] ?? null;

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        if ($includePort && isset($parts['port'])) {
            return $host.':'.$parts['port'];
        }

        return $host;
    }

    /**
     * Cookie Domain attribute: host only, no scheme/path/port.
     */
    public static function cookieDomain(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        $withoutScheme = (string) preg_replace('#^https?://#i', '', $trimmed);
        $leadingDot = str_starts_with($withoutScheme, '.');
        $host = self::fromUrlOrHost($trimmed, includePort: false);

        if ($host === null) {
            return null;
        }

        if ($leadingDot && ! str_starts_with($host, '.')) {
            return '.'.$host;
        }

        return $host;
    }

    /**
     * @param  list<string|null>  $extra
     * @return list<string>
     */
    public static function list(?string $csv, array $extra = []): array
    {
        $items = array_map('trim', explode(',', (string) $csv));
        $hosts = [];

        foreach (array_merge($items, $extra) as $item) {
            if (! is_string($item) || trim($item) === '') {
                continue;
            }

            $host = self::fromUrlOrHost($item);

            if ($host !== null) {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique($hosts));
    }
}
