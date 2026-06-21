<?php

declare(strict_types=1);

namespace Capell\Navigation\Support;

/**
 * Centralised allowlist for navigation item URLs.
 *
 * Blade escapes HTML entities but does NOT strip dangerous URL schemes, so a
 * stored url of `javascript:alert(1)` (or `data:`/`vbscript:`/`file:`) becomes a
 * clickable XSS vector. This helper is the single source of truth for which
 * schemes/shapes are permitted, used both at write-time (Filament validation)
 * and render-time (defence for legacy/bad stored rows).
 */
final class SafeUrl
{
    /**
     * Schemes that may not appear in a navigation URL.
     *
     * @var non-empty-list<'http'|'https'|'mailto'|'tel'>
     */
    private const array ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Determine whether a navigation URL is safe to render as an `href`.
     *
     * Permits absolute http(s)/mailto/tel URLs and relative paths/anchors
     * (`/path`, `#anchor`, `?query`, `./` and `../`). Rejects dangerous schemes
     * such as `javascript:`, `data:`, `vbscript:` and `file:`, protocol-relative
     * `//host` URLs (which inherit the page scheme and can be abused), and any
     * value containing control characters.
     */
    public static function isSafe(string $url): bool
    {
        $trimmedUrl = trim($url);

        if ($trimmedUrl === '' || preg_match('/[\x00-\x1F\x7F]/', $trimmedUrl) === 1) {
            return false;
        }

        $scheme = parse_url($trimmedUrl, PHP_URL_SCHEME);

        if (is_string($scheme)) {
            return in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true);
        }

        if (str_starts_with($trimmedUrl, '//')) {
            return false;
        }

        return str_starts_with($trimmedUrl, '/')
            || str_starts_with($trimmedUrl, '#')
            || str_starts_with($trimmedUrl, '?')
            || str_starts_with($trimmedUrl, './')
            || str_starts_with($trimmedUrl, '../');
    }

    /**
     * Render-time defence: return the URL when it passes the allowlist,
     * otherwise null so the link is neutralised rather than executed.
     */
    public static function sanitise(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        return self::isSafe($url) ? $url : null;
    }
}
