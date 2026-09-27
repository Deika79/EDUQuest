<?php

namespace App\Support;

use App\Enums\VideoProvider;

class VideoReference
{
    public static function extractId(?VideoProvider $provider, string $reference): ?string
    {
        $reference = trim($reference);

        if ($provider === VideoProvider::YouTube) {
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $reference) === 1) {
                return $reference;
            }

            $parts = parse_url($reference);
            $host = strtolower($parts['host'] ?? '');
            $candidate = null;

            if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
                parse_str($parts['query'] ?? '', $query);
                $candidate = $query['v'] ?? null;

                if (! $candidate && preg_match('#^/(?:embed|shorts)/([^/?]+)#', $parts['path'] ?? '', $matches)) {
                    $candidate = $matches[1];
                }
            } elseif ($host === 'youtu.be') {
                $candidate = trim($parts['path'] ?? '', '/');
            }

            return is_string($candidate) && preg_match('/^[A-Za-z0-9_-]{11}$/', $candidate) === 1
                ? $candidate
                : null;
        }

        if ($provider === VideoProvider::Vimeo) {
            if (preg_match('/^[0-9]{6,12}$/', $reference) === 1) {
                return $reference;
            }

            $parts = parse_url($reference);
            $host = strtolower($parts['host'] ?? '');

            if (! in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true)) {
                return null;
            }

            $segments = array_values(array_filter(explode('/', $parts['path'] ?? '')));
            $candidate = end($segments);

            return is_string($candidate) && preg_match('/^[0-9]{6,12}$/', $candidate) === 1
                ? $candidate
                : null;
        }

        return null;
    }
}
