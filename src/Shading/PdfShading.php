<?php

declare(strict_types=1);

namespace PhpPdf\Shading;

use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfObjectRegistry;

/**
 * A compiled PDF shading pattern that can be registered on a page.
 *
 * Implementations produce the shading dictionary (and any dependent Function
 * objects) as registered indirect objects and return a reference to the
 * top-level shading dictionary.
 *
 * Register the shading on a page with PdfPageBuilder::useShading(), then
 * paint it inside the content stream with:
 *
 *   $stream->saveGraphicsState()
 *          ->rectangle($x, $y, $w, $h)->clip()->endPath()
 *          ->paintShading('MyShadingName')
 *          ->restoreGraphicsState();
 */
interface PdfShading
{
    public function compile(PdfObjectRegistry $registry): PdfIndirectReference;
}
