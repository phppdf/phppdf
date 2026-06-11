<?php

declare(strict_types=1);

namespace PhpPdf\Color;

/**
 * The colour model used by a Color instance.
 *
 * Each value maps to a distinct set of PDF device-colour operators:
 *   Gray → G / g (single lightness component 0–1)
 *   Rgb → RG / rg (three components: red, green, blue 0–1)
 *   Cmyk → K / k (four components: cyan, magenta, yellow, key 0–1)
 */
enum ColorType
{
    case Gray;
    case Rgb;
    case Cmyk;
}
