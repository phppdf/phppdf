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
 * A PDF Type 3 (radial) shading pattern.
 *
 * Paints a gradient between two circles in page space. All coordinates are in
 * page-space points (origin at bottom-left of the page).
 *
 * Simple circular gradient (point center expanding outward):
 *
 *   $page->useShading('Radial', PdfRadialShading::circle(
 *       cx: 297, cy: 421, radius: 150,
 *       colorCenter: Color::white(),
 *       colorEdge: Color::navy(),
 *   ));
 *
 * Two-circle gradient (spotlight / offset focal point):
 *
 *   $page->useShading('Spot', PdfRadialShading::between(
 *       cx0: 310, cy0: 440, r0: 0,
 *       cx1: 297, cy1: 421, r1: 150,
 *       colorStart: Color::yellow(),
 *       colorEnd: Color::fromHex('#6600cc'),
 *   ));
 *
 * Multi-stop circular gradient:
 *
 *   $page->useShading('Multi', PdfRadialShading::multiStop(
 *       cx0: 297, cy0: 421, r0: 0,
 *       cx1: 297, cy1: 421, r1: 150,
 *       stops: [
 *           new ColorStop(0.0, Color::white()),
 *           new ColorStop(0.5, Color::fromHex('#4488ff')),
 *           new ColorStop(1.0, Color::navy()),
 *       ],
 *   ));
 *
 * Paint inside a clipping region:
 *
 *   $stream->saveGraphicsState()
 *          ->rectangle($x, $y, $w, $h)->clip()->endPath()
 *          ->paintShading('Radial')
 *          ->restoreGraphicsState();
 */
final class PdfRadialShading implements PdfShading
{
    /** @param list<\PhpPdf\Shading\ColorStop> $stops */
    private function __construct(
        private readonly float $cx0,
        private readonly float $cy0,
        private readonly float $r0,
        private readonly float $cx1,
        private readonly float $cy1,
        private readonly float $r1,
        private readonly array $stops,
        private readonly bool $extend,
    ) {
    }

    /**
     * Creates a circular gradient that expands from a point at the center outward.
     *
     * This is the most common radial gradient: the inner circle degenerates to a
     * point (r0 = 0) and the outer circle defines the edge of the gradient area.
     */
    public static function circle(
        float $cx,
        float $cy,
        float $radius,
        Color $colorCenter,
        Color $colorEdge,
        bool $extend = true,
    ): self {
        return new self($cx, $cy, 0.0, $cx, $cy, $radius, [
            new ColorStop(0.0, $colorCenter),
            new ColorStop(1.0, $colorEdge),
        ], $extend);
    }

    /**
     * Creates a two-stop gradient between two arbitrary circles.
     *
     * Use this for offset focal points (spotlight effect) or when the inner
     * circle has a non-zero radius. The gradient maps $colorStart to the first
     * circle and $colorEnd to the second.
     */
    public static function between(
        float $cx0,
        float $cy0,
        float $r0,
        float $cx1,
        float $cy1,
        float $r1,
        Color $colorStart,
        Color $colorEnd,
        bool $extend = true,
    ): self {
        return new self($cx0, $cy0, $r0, $cx1, $cy1, $r1, [
            new ColorStop(0.0, $colorStart),
            new ColorStop(1.0, $colorEnd),
        ], $extend);
    }

    /**
     * Creates a multi-stop radial gradient between two circles.
     *
     * @param list<\PhpPdf\Shading\ColorStop> $stops At least two stops; first offset must be 0.0, last must be 1.0.
     */
    public static function multiStop(
        float $cx0,
        float $cy0,
        float $r0,
        float $cx1,
        float $cy1,
        float $r1,
        array $stops,
        bool $extend = true,
    ): self {
        if (count($stops) < 2) {
            throw new InvalidArgumentException('At least two colour stops are required.');
        }

        return new self($cx0, $cy0, $r0, $cx1, $cy1, $r1, $stops, $extend);
    }

    public function compile(PdfObjectRegistry $registry): PdfIndirectReference
    {
        $functionRef = ShadingFunctions::buildFunction($registry, $this->stops);
        $colorSpace = ShadingFunctions::colorSpaceName($this->stops[0]->color);

        $shading = new PdfDictionary([
            'ColorSpace' => new PdfName($colorSpace),
            'Coords' => new PdfArray([
                new PdfReal($this->cx0), new PdfReal($this->cy0), new PdfReal($this->r0),
                new PdfReal($this->cx1), new PdfReal($this->cy1), new PdfReal($this->r1),
            ]),
            'Extend' => new PdfArray([
                new PdfBoolean($this->extend),
                new PdfBoolean($this->extend),
            ]),
            'Function' => $functionRef,
            'ShadingType' => new PdfInteger(3),
        ]);

        return $registry->register($shading);
    }
}
