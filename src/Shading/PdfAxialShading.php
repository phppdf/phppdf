<?php

declare(strict_types=1);

namespace PhpPdf\Shading;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;

/**
 * A PDF Type 2 (axial / linear) shading pattern.
 *
 * Paints a linear gradient between two points in page space. All coordinates
 * are in page-space points (the same units used by rectangle(), lineTo(), etc).
 *
 * Two-stop gradient:
 *
 *   $page->useShading('HGrad', PdfAxialShading::between(
 *       x0: 72, y0: 0, x1: 523, y1: 0,
 *       colorStart: Color::fromHex('#e63b3b'),
 *       colorEnd: Color::navy(),
 *   ));
 *
 * Multi-stop gradient:
 *
 *   $page->useShading('RYG', PdfAxialShading::multiStop(
 *       x0: 72, y0: 500, x1: 523, y1: 500,
 *       stops: [
 *           new ColorStop(0.0, Color::red()),
 *           new ColorStop(0.5, Color::yellow()),
 *           new ColorStop(1.0, Color::lime()),
 *       ],
 *   ));
 *
 * Paint inside a clipping region:
 *
 *   $stream->saveGraphicsState()
 *          ->rectangle($x, $y, $w, $h)->clip()->endPath()
 *          ->paintShading('HGrad')
 *          ->restoreGraphicsState();
 */
final class PdfAxialShading implements PdfShading
{
    /** @param list<\PhpPdf\Shading\ColorStop> $stops */
    private function __construct(
        private readonly float $x0,
        private readonly float $y0,
        private readonly float $x1,
        private readonly float $y1,
        private readonly array $stops,
        private readonly bool $extend,
    ) {
    }

    /**
     * Creates a two-stop linear gradient between ($x0,$y0) and ($x1,$y1).
     *
     * The direction and length of the gradient are determined by these two
     * axis points. Use a horizontal pair for a left-to-right gradient, a
     * vertical pair for top-to-bottom, or any angle for a diagonal fill.
     */
    public static function between(
        float $x0,
        float $y0,
        float $x1,
        float $y1,
        Color $colorStart,
        Color $colorEnd,
        bool $extend = true,
    ): self {
        return new self($x0, $y0, $x1, $y1, [
            new ColorStop(0.0, $colorStart),
            new ColorStop(1.0, $colorEnd),
        ], $extend);
    }

    /**
     * Creates a multi-stop linear gradient between ($x0,$y0) and ($x1,$y1).
     *
     * @param list<\PhpPdf\Shading\ColorStop> $stops At least two stops; first offset must be 0.0, last must be 1.0.
     */
    public static function multiStop(
        float $x0,
        float $y0,
        float $x1,
        float $y1,
        array $stops,
        bool $extend = true,
    ): self {
        if (count($stops) < 2) {
            throw new InvalidArgumentException('At least two colour stops are required.');
        }

        return new self($x0, $y0, $x1, $y1, $stops, $extend);
    }

    public function compile(PdfObjectRegistry $registry): PdfIndirectReference
    {
        $functionRef = ShadingFunctions::buildFunction($registry, $this->stops);
        $colorSpace = ShadingFunctions::colorSpaceName($this->stops[0]->color);

        $shading = new PdfDictionary([
            'ColorSpace' => new PdfName($colorSpace),
            'Coords' => new PdfArray([
                new PdfReal($this->x0), new PdfReal($this->y0),
                new PdfReal($this->x1), new PdfReal($this->y1),
            ]),
            'Extend' => new PdfArray([
                new PdfBoolean($this->extend),
                new PdfBoolean($this->extend),
            ]),
            'Function' => $functionRef,
            'ShadingType' => new PdfInteger(2),
        ]);

        return $registry->register($shading);
    }
}
