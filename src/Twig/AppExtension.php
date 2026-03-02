<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Adds the |json_decode filter to Twig templates.
 *
 * Usage: {{ someJsonString|json_decode }}
 * Returns an object (stdClass) or array, or null on failure.
 */
class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
        ];
    }

    public function jsonDecode(string $json): mixed
    {
        $decoded = json_decode($json, associative: true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : null;
    }
}
