<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use PhpPdf\Content\BlendMode;

/**
 * An ExtGState dictionary controlling transparency and compositing.
 *
 * Sets fill opacity (ca), stroke opacity (CA), and the compositing blend
 * mode (BM). Register the dictionary as a named resource on the page with
 * PdfPageBuilder::useGraphicsState() and activate it inside the content
 * stream with setGraphicsStateParameters(). Requires PDF 1.4.
 *
 * Usage:
 *
 *   $page->useGraphicsState('GS1', new PdfGraphicsStateDictionary(fillAlpha: 0.5));
 *   $page->useGraphicsState('GS2', new PdfGraphicsStateDictionary(
 *       fillAlpha: 1.0,
 *       strokeAlpha: 0.4,
 *       blendMode: BlendMode::Multiply,
 *   ));
 *
 *   // Inside content():
 *   $stream->setGraphicsStateParameters('GS1')
 *          ->fillColor(Color::rgb(1, 0, 0))
 *          ->rectangle(72, 600, 200, 100)
 *          ->fill();
 */
final class PdfGraphicsStateDictionary extends PdfDictionary
{
    public function __construct(
        float $fillAlpha = 1.0,
        float $strokeAlpha = 1.0,
        BlendMode $blendMode = BlendMode::Normal,
    ) {
        $fillAlpha = max(0.0, min(1.0, $fillAlpha));
        $strokeAlpha = max(0.0, min(1.0, $strokeAlpha));

        parent::__construct([
            'BM' => new PdfName($blendMode->value),
            'ca' => new PdfReal($fillAlpha),
            'CA' => new PdfReal($strokeAlpha),
            'Type' => new PdfName('ExtGState'),
        ]);
    }
}
