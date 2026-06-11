<?php

declare(strict_types=1);

namespace PhpPdf\Barcode;

/**
 * Common interface for 1-D (linear) barcode encoders.
 *
 * The bar array uses run-length encoding: alternating integers that represent
 * the width of a bar followed by the width of a space, in narrow-module units.
 * Index 0 is always a bar width.
 *
 * Implemented by Code128 and EAN13.
 */
interface LinearBarcode
{
    /**
     * Alternating bar/space widths in narrow-module units.
     * The first element is always a bar width.
     *
     * @return list<int>
     */
    public function getBars(): array;

    /**
     * Human-readable representation of the encoded data.
     * Shown as text below the barcode when drawBarcode() is called with a font.
     */
    public function getText(): string;
}
