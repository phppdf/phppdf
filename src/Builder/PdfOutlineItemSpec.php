<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

/**
 * Internal value object carrying the data for one outline item.
 *
 * Consumed by PdfDocumentBuilder when it compiles the outline tree into
 * indirect objects. Not part of the public API.
 */
final class PdfOutlineItemSpec
{
    public function __construct(
        public readonly string $title,
        public readonly int $pageIndex,
        public readonly PdfOutlineBuilder $children,
    ) {
    }
}
