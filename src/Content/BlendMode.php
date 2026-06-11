<?php

declare(strict_types=1);

namespace PhpPdf\Content;

/**
 * PDF compositing blend modes (ISO 32000-1, §11.3.5).
 *
 * The blend mode controls how a new object's colour is combined with the
 * colours already painted on the page. Apply a blend mode by registering a
 * PdfGraphicsStateDictionary with PdfPageBuilder::useGraphicsState() and then
 * activating it in a content stream with setGraphicsStateParameters().
 * Transparency support requires at least PDF 1.4.
 *
 * Separable modes (operate independently on each colour channel):
 *   Normal — no blending; the source colour replaces the destination.
 *   Multiply — darkens: source × destination. White is neutral.
 *   Screen — brightens: 1 − (1−src)×(1−dst). Black is neutral.
 *   Overlay — combines Multiply and Screen; preserves highlight/shadow.
 *   Darken — retains the darker of source and destination per channel.
 *   Lighten — retains the lighter of source and destination per channel.
 *   ColorDodge — brightens destination by dividing by (1 − source).
 *   ColorBurn — darkens destination by dividing by source and inverting.
 *   HardLight — like Overlay but with source and destination swapped.
 *   SoftLight — soft version of HardLight; gentle highlight/shadow.
 *   Difference — absolute difference of source and destination channels.
 *   Exclusion — like Difference but lower contrast.
 *
 * Non-separable modes (operate on colour as a whole):
 *   Hue — hue of source, saturation + luminosity of destination.
 *   Saturation — saturation of source, hue + luminosity of destination.
 *   Color — hue + saturation of source, luminosity of destination.
 *   Luminosity — luminosity of source, hue + saturation of destination.
 */
enum BlendMode: string
{
    case Normal = 'Normal';
    case Multiply = 'Multiply';
    case Screen = 'Screen';
    case Overlay = 'Overlay';
    case Darken = 'Darken';
    case Lighten = 'Lighten';
    case ColorDodge = 'ColorDodge';
    case ColorBurn = 'ColorBurn';
    case HardLight = 'HardLight';
    case SoftLight = 'SoftLight';
    case Difference = 'Difference';
    case Exclusion = 'Exclusion';
    case Hue = 'Hue';
    case Saturation = 'Saturation';
    case Color = 'Color';
    case Luminosity = 'Luminosity';
}
