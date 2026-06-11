<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

/**
 * Internal value object for a pending link annotation on a page.
 *
 * Stores the clickable area and either a URI (external) or a 0-based page
 * index (internal GoTo). The action object and annotation are materialised
 * in PdfPageBuilder::compileLinks() once all page references are known.
 */
final class PdfLinkSpec
{
    private function __construct(
        public readonly float $x,
        public readonly float $y,
        public readonly float $width,
        public readonly float $height,
        public readonly ?string $uri,
        public readonly ?int $pageIndex,
    ) {
    }

    public static function uri(float $x, float $y, float $width, float $height, string $uri): self
    {
        return new self($x, $y, $width, $height, $uri, null);
    }

    public static function page(float $x, float $y, float $width, float $height, int $pageIndex): self
    {
        return new self($x, $y, $width, $height, null, $pageIndex);
    }
}
