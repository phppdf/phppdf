<?php

declare(strict_types=1);

namespace PhpPdf\Svg;

use DOMDocument;
use DOMElement;
use RuntimeException;

/**
 * An SVG document ready for embedding in a PDF as a Form XObject.
 *
 * Parses the SVG's viewBox (or width/height attributes) to determine the
 * natural dimensions used to scale the form when drawn on a page.
 *
 * Usage:
 *   $svg = SvgDocument::fromFile('/path/to/logo.svg');
 *   $svg = SvgDocument::fromString('<svg ...>...</svg>');
 *
 * Then register it on a page with PdfPageBuilder::useSvg() and draw it
 * with PdfContentStreamBuilder::drawSvg().
 */
final class SvgDocument
{
    private function __construct(
        private readonly DOMDocument $dom,
        private readonly float $width,
        private readonly float $height,
    ) {
    }

    public static function fromFile(string $path): self
    {
        if (!is_readable($path)) {
            throw new RuntimeException("SVG file not readable: {$path}");
        }

        return self::fromString((string) file_get_contents($path));
    }

    public static function fromString(string $xml): self
    {
        $dom = new DOMDocument();

        $prev = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xml);
        libxml_use_internal_errors($prev);

        if (!$ok) {
            throw new RuntimeException('Failed to parse SVG XML.');
        }

        $root = $dom->documentElement;

        if ($root === null || $root->localName !== 'svg') {
            throw new RuntimeException('Root element is not <svg>.');
        }

        [$width, $height] = self::parseDimensions($root);

        return new self($dom, $width, $height);
    }

    public function getWidth(): float
    {
        return $this->width;
    }

    public function getHeight(): float
    {
        return $this->height;
    }

    public function getDom(): DOMDocument
    {
        return $this->dom;
    }

    /** @return array{float, float} */
    private static function parseDimensions(DOMElement $root): array
    {
        if ($root->hasAttribute('viewBox')) {
            $parts = preg_split('/[\s,]+/', trim($root->getAttribute('viewBox'))) ?: [];

            if (count($parts) === 4 && (float) $parts[2] > 0 && (float) $parts[3] > 0) {
                return [(float) $parts[2], (float) $parts[3]];
            }
        }

        $w = self::parseLengthAttr($root, 'width');
        $h = self::parseLengthAttr($root, 'height');

        if ($w > 0 && $h > 0) {
            return [$w, $h];
        }

        // Fallback: standard default viewport
        return [100.0, 100.0];
    }

    private static function parseLengthAttr(DOMElement $el, string $attr): float
    {
        $val = $el->getAttribute($attr);

        if ($val === '') {
            return 0.0;
        }

        // Strip units (px, pt, em, etc.) — treat as user units
        return (float) preg_replace('/[^0-9.\-]/', '', $val);
    }
}
