<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

use const PREG_SET_ORDER;

/**
 * Stateless CSS parsing utilities.
 *
 * All methods are pure functions that convert CSS text into PHP data structures.
 * No external dependencies are required — this is a best-effort parser targeting
 * the subset of CSS needed by the HTML converter (colours, lengths, fonts,
 * text alignment, margins, padding, and simple element/class selectors).
 */
final class CssParser
{
    // -------------------------------------------------------------------------
    // Inline style parsing
    // -------------------------------------------------------------------------

    /**
     * Parses a CSS inline style attribute value into a property → value map.
     *
     * Example:
     *   "color: red; font-size: 14px" → ['color' => 'red', 'font-size' => '14px']
     *
     * Property names are lower-cased; values are returned as-is (trimmed).
     *
     * @return array<string, string>
     */
    public static function parseInline(string $style): array
    {
        $result = [];

        foreach (explode(';', $style) as $declaration) {
            $declaration = trim($declaration);

            if ($declaration === '') {
                continue;
            }

            $pos = strpos($declaration, ':');

            if ($pos === false) {
                continue;
            }

            $property = strtolower(trim(substr($declaration, 0, $pos)));
            $value = trim(substr($declaration, $pos + 1));
            $result[$property] = $value;
        }

        return $result;
    }

    // -------------------------------------------------------------------------
    // Stylesheet parsing
    // -------------------------------------------------------------------------

    /**
     * Parses a CSS stylesheet string into a selector → declarations map.
     *
     * Supports:
     * - Element selectors: `p { … }`, `h1 { … }`
     * - Class selectors: `.highlight { … }`
     * - Comma groups: `h1, h2 { … }`
     *
     * Does NOT support descendant combinators, pseudo-classes, ID selectors,
     * or @-rules. Suitable for the simple stylesheets typically embedded in
     * HTML → PDF conversion scenarios.
     *
     * @return array<string, array<string, string>>
     */
    public static function parseStylesheet(string $css): array
    {
        $rules = [];

        // Strip C-style /* … */ comments.
        $css = preg_replace('#/\*.*?\*/#s', '', $css) ?? $css;

        // Match each rule block:  selector(s) { declaration block }
        preg_match_all('/([^{}]+)\{([^{}]*)\}/s', $css, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $declarations = self::parseInline($match[2]);

            if ($declarations === []) {
                continue;
            }

            foreach (array_map('trim', explode(',', $match[1])) as $selector) {
                $selector = strtolower($selector);

                if ($selector === '') {
                    continue;
                }

                $rules[$selector] = array_merge($rules[$selector] ?? [], $declarations);
            }
        }

        return $rules;
    }

    // -------------------------------------------------------------------------
    // Value parsing helpers
    // -------------------------------------------------------------------------

    /**
     * Converts a CSS length value to PDF points.
     *
     * Supported units:
     *   pt → as-is
     *   px → × 0.75 (96 dpi → 72 pt/in)
     *   em → × $parentFontSize
     *   rem → × $baseFontSize
     *   (unitless integer) → treated as px
     *
     * Returns null for unsupported values (percentages, viewport units, etc.).
     */
    public static function parseLength(string $value, float $parentFontSize, float $baseFontSize): ?float
    {
        $value = strtolower(trim($value));

        if ($value === '0') {
            return 0.0;
        }

        if (preg_match('/^(-?[\d.]+)(pt|px|em|rem)$/', $value, $m)) {
            $num = (float) $m[1];

            return match ($m[2]) {
                'pt' => $num,
                'px' => $num * 0.75,
                'em' => $num * $parentFontSize,
                'rem' => $num * $baseFontSize,
            };
        }

        // Bare integer — treat as pixels.
        if (ctype_digit(ltrim($value, '-'))) {
            return (float) $value * 0.75;
        }

        return null;
    }

    /**
     * Parses a CSS color value into an [r, g, b] array with components in 0.0–1.0.
     *
     * Supported formats:
     *   #rrggbb #rgb rgb(r, g, b) and a small set of named colors.
     *
     * Returns null for 'transparent' or unrecognised values.
     *
     * @return array{float,float,float}|null
     */
    public static function parseColor(string $value): ?array
    {
        $value = strtolower(trim($value));

        $named = self::namedColor($value);

        if ($named !== null) {
            return $named;
        }

        // Hex: #rrggbb or #rgb
        if (preg_match('/^#([0-9a-f]{3,6})$/i', $value, $m)) {
            $hex = $m[1];

            if (strlen($hex) === 3) {
                $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
            }

            if (strlen($hex) === 6) {
                return [
                    hexdec(substr($hex, 0, 2)) / 255.0,
                    hexdec(substr($hex, 2, 2)) / 255.0,
                    hexdec(substr($hex, 4, 2)) / 255.0,
                ];
            }
        }

        // rgb(r, g, b) — integer components 0-255
        if (preg_match('/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $value, $m)) {
            return [(int) $m[1] / 255.0, (int) $m[2] / 255.0, (int) $m[3] / 255.0];
        }

        return null;
    }

    // -------------------------------------------------------------------------
    // Named colour table
    // -------------------------------------------------------------------------

    /**
     * Resolves a CSS named colour to [r, g, b], or null if unknown.
     *
     * Values match the CSS Color Level 4 specification for the colours listed.
     *
     * @return array{float,float,float}|null
     */
    private static function namedColor(string $name): ?array
    {
        return match ($name) {
            'black' => [0.000, 0.000, 0.000],
            'white' => [1.000, 1.000, 1.000],
            'red' => [1.000, 0.000, 0.000],
            'lime' => [0.000, 1.000, 0.000],
            'green' => [0.000, 0.502, 0.000],
            'blue' => [0.000, 0.000, 1.000],
            'yellow' => [1.000, 1.000, 0.000],
            'cyan', 'aqua' => [0.000, 1.000, 1.000],
            'magenta', 'fuchsia' => [1.000, 0.000, 1.000],
            'orange' => [1.000, 0.647, 0.000],
            'purple' => [0.502, 0.000, 0.502],
            'pink' => [1.000, 0.753, 0.796],
            'gray', 'grey' => [0.502, 0.502, 0.502],
            'darkgray', 'darkgrey' => [0.663, 0.663, 0.663],
            'lightgray', 'lightgrey' => [0.827, 0.827, 0.827],
            'silver' => [0.753, 0.753, 0.753],
            'navy' => [0.000, 0.000, 0.502],
            'teal' => [0.000, 0.502, 0.502],
            'maroon' => [0.502, 0.000, 0.000],
            'olive' => [0.502, 0.502, 0.000],
            'coral' => [1.000, 0.498, 0.314],
            'salmon' => [0.980, 0.502, 0.447],
            'gold' => [1.000, 0.843, 0.000],
            'indigo' => [0.294, 0.000, 0.510],
            'violet' => [0.933, 0.510, 0.933],
            'brown' => [0.647, 0.165, 0.165],
            'transparent' => null,
            default => null,
        };
    }
}
