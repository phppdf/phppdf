<?php

declare(strict_types=1);

namespace PhpPdf\Shading;

use InvalidArgumentException;
use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfReal;

/**
 * Internal helpers for building PDF Function dictionaries used by shading patterns.
 *
 * Two-stop gradients use a single Type 2 (Exponential Interpolation) function.
 * Multi-stop gradients use a Type 3 (Stitching) function that chains one Type 2
 * function per adjacent pair of stops.
 */
final class ShadingFunctions
{
    private function __construct()
    {
    }

    /**
     * Registers the appropriate PDF Function for the given colour stops and
     * returns an indirect reference to it.
     *
     * @param list<\PhpPdf\Shading\ColorStop> $stops Sorted stops; first offset must be 0.0, last must be 1.0.
     */
    public static function buildFunction(PdfObjectRegistry $registry, array $stops): PdfIndirectReference
    {
        self::validateStops($stops);

        if (count($stops) === 2) {
            return $registry->register(self::type2($stops[0]->color, $stops[1]->color));
        }

        return self::buildStitching($registry, $stops);
    }

    /**
     * Returns the PDF DeviceXxx color-space name for the given colour's model.
     */
    public static function colorSpaceName(Color $color): string
    {
        return match ($color->getType()) {
            ColorType::Gray => 'DeviceGray',
            ColorType::Rgb => 'DeviceRGB',
            ColorType::Cmyk => 'DeviceCMYK',
        };
    }

    // -------------------------------------------------------------------------

    /**
     * Builds a Type 2 Exponential Interpolation function that maps [0,1] → colour.
     */
    private static function type2(Color $start, Color $end): PdfDictionary
    {
        return new PdfDictionary([
            'C0' => self::colorArray($start),
            'C1' => self::colorArray($end),
            'Domain' => new PdfArray([new PdfReal(0), new PdfReal(1)]),
            'FunctionType' => new PdfInteger(2),
            'N' => new PdfInteger(1),
        ]);
    }

    /**
     * Builds a Type 3 Stitching function that chains one Type 2 sub-function
     * per adjacent pair of stops.
     *
     * @param list<\PhpPdf\Shading\ColorStop> $stops Validated, sorted, spanning [0,1].
     */
    private static function buildStitching(PdfObjectRegistry $registry, array $stops): PdfIndirectReference
    {
        $n = count($stops);

        $functions = [];
        $bounds = [];
        $encode = [];

        for ($i = 0; $i < $n - 1; $i++) {
            $functions[] = $registry->register(self::type2($stops[$i]->color, $stops[$i + 1]->color));

            // Interior breakpoints only — exclude the first stop's offset (0).
            if ($i > 0) {
                $bounds[] = new PdfReal($stops[$i]->offset);
            }

            $encode[] = new PdfReal(0);
            $encode[] = new PdfReal(1);
        }

        $stitching = new PdfDictionary([
            'Bounds' => new PdfArray($bounds),
            'Domain' => new PdfArray([new PdfReal(0), new PdfReal(1)]),
            'Encode' => new PdfArray($encode),
            'Functions' => new PdfArray($functions),
            'FunctionType' => new PdfInteger(3),
        ]);

        return $registry->register($stitching);
    }

    private static function colorArray(Color $color): PdfArray
    {
        return new PdfArray(array_map(static fn ($c) => new PdfReal($c), $color->getComponents()));
    }

    /** @param list<\PhpPdf\Shading\ColorStop> $stops */
    private static function validateStops(array $stops): void
    {
        if (count($stops) < 2) {
            throw new InvalidArgumentException('A gradient requires at least two colour stops.');
        }

        $firstType = $stops[0]->color->getType();

        foreach ($stops as $stop) {
            if ($stop->color->getType() !== $firstType) {
                throw new InvalidArgumentException(
                    'All colour stops must use the same colour model (Gray, RGB, or CMYK).',
                );
            }
        }

        if (abs($stops[0]->offset) > 1e-9) {
            throw new InvalidArgumentException('The first colour stop must have offset 0.0.');
        }

        if (abs($stops[count($stops) - 1]->offset - 1.0) > 1e-9) {
            throw new InvalidArgumentException('The last colour stop must have offset 1.0.');
        }

        for ($i = 1; $i < count($stops); $i++) {
            if ($stops[$i]->offset <= $stops[$i - 1]->offset) {
                throw new InvalidArgumentException(
                    "Colour stop offsets must be strictly increasing; got "
                    . "{$stops[$i - 1]->offset} followed by {$stops[$i]->offset}.",
                );
            }
        }
    }
}
