<?php

declare(strict_types=1);

namespace PhpPdf\Shading;

use InvalidArgumentException;
use PhpPdf\Color\Color;

/**
 * A single colour position in a gradient, mapping an offset in [0.0, 1.0] to a colour.
 *
 * Passed to PdfAxialShading::multiStop() and PdfRadialShading::multiStop()
 * to define a gradient with more than two colours:
 *
 *   PdfAxialShading::multiStop(
 *       x0: 72, y0: 500, x1: 523, y1: 500,
 *       stops: [
 *           new ColorStop(0.0, Color::red()),
 *           new ColorStop(0.5, Color::yellow()),
 *           new ColorStop(1.0, Color::blue()),
 *       ],
 *   )
 */
final class ColorStop
{
    public function __construct(public readonly float $offset, public readonly Color $color,)
    {
        if ($offset < 0.0 || $offset > 1.0) {
            throw new InvalidArgumentException("ColorStop offset must be in [0.0, 1.0], got {$offset}.");
        }
    }
}
