<?php

declare(strict_types=1);

namespace PhpPdf\Font;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Object\PdfToUnicodeCMap;

/**
 * Static helpers for compiling font resources into a PdfObjectRegistry.
 *
 * Shared by PdfPageBuilder (new pages) and PdfDocumentEditor (post-build
 * header/footer injection). Keeping this logic in one place avoids duplicating
 * the composite-font structure across both callers.
 */
final class PdfFontCompiler
{
    // @codeCoverageIgnore
    private function __construct()
    {
    }

    /**
     * Compiles a standard Type1 font dictionary into $registry and returns
     * an indirect reference to it.
     */
    public static function compileType1(PdfObjectRegistry $registry, string $baseFont): PdfIndirectReference
    {
        return $registry->register(new PdfDictionary([
            'BaseFont' => new PdfName($baseFont),
            'Encoding' => new PdfName('WinAnsiEncoding'),
            'Subtype' => new PdfName('Type1'),
            'Type' => new PdfName('Font'),
        ]));
    }

    /**
     * Builds the full composite font structure in $registry:
     *   FontFile2/3 stream → FontDescriptor → CIDFont → ToUnicode stream → Type0 font
     *
     * @param array<int,int> $usedGlyphs [glyphId => codePoint]
     */
    public static function compileEmbedded(
        PdfObjectRegistry $registry,
        TrueTypeFont $font,
        array $usedGlyphs,
    ): PdfIndirectReference {
        // 1. Font program stream
        $fontData = $font->subset($usedGlyphs);
        $fontFileKey = $font->isCff()
            ? 'FontFile3'
            : 'FontFile2';
        $fontFileDict = new PdfDictionary([
            'Length1' => new PdfInteger(strlen($fontData)),
        ]);

        if ($font->isCff()) {
            $fontFileDict->set('Subtype', new PdfName('CIDFontType0C'));
        }

        $fontFileRef = $registry->register(new PdfStream($fontFileDict, new PdfRawStreamData($fontData)));

        // 2. FontDescriptor
        [$xMin, $yMin, $xMax, $yMax] = $font->getFontBBox();
        $fontDescRef = $registry->register(new PdfDictionary([
            $fontFileKey => $fontFileRef,
            'Ascent' => new PdfInteger($font->toPdfUnits($font->getAscent())),
            'CapHeight' => new PdfInteger($font->toPdfUnits($font->getCapHeight())),
            'Descent' => new PdfInteger($font->toPdfUnits($font->getDescent())),
            'Flags' => new PdfInteger($font->getFlags()),
            'FontBBox' => new PdfArray([
                new PdfInteger($font->toPdfUnits($xMin)),
                new PdfInteger($font->toPdfUnits($yMin)),
                new PdfInteger($font->toPdfUnits($xMax)),
                new PdfInteger($font->toPdfUnits($yMax)),
            ]),
            'FontName' => new PdfName($font->getFontName()),
            'ItalicAngle' => new PdfReal($font->getItalicAngle()),
            'StemV' => new PdfInteger($font->getStemV()),
            'Type' => new PdfName('FontDescriptor'),
        ]));

        // 3. CIDFont glyph widths (/W)
        $widthsArray = self::buildWidthsArray($font, $usedGlyphs);

        // 4. CIDFont dictionary
        $cidSubtype = $font->isCff()
            ? 'CIDFontType0'
            : 'CIDFontType2';
        $cidFontDict = new PdfDictionary([
            'BaseFont' => new PdfName($font->getFontName()),
            'CIDSystemInfo' => new PdfDictionary([
                'Ordering' => new PdfString('Identity'),
                'Registry' => new PdfString('Adobe'),
                'Supplement' => new PdfInteger(0),
            ]),
            'DW' => new PdfInteger(1000),
            'FontDescriptor' => $fontDescRef,
            'Subtype' => new PdfName($cidSubtype),
            'Type' => new PdfName('Font'),
            'W' => $widthsArray,
        ]);

        if (!$font->isCff()) {
            $cidFontDict->set('CIDToGIDMap', new PdfName('Identity'));
        }

        $cidFontRef = $registry->register($cidFontDict);

        // 5. ToUnicode CMap
        $toUnicodeRef = $registry->register(new PdfToUnicodeCMap($usedGlyphs));

        // 6. Type0 composite font
        return $registry->register(new PdfDictionary([
            'BaseFont' => new PdfName($font->getFontName()),
            'DescendantFonts' => new PdfArray([$cidFontRef]),
            'Encoding' => new PdfName('Identity-H'),
            'Subtype' => new PdfName('Type0'),
            'ToUnicode' => $toUnicodeRef,
            'Type' => new PdfName('Font'),
        ]));
    }

    /**
     * Groups used glyph IDs by consecutive runs to produce a compact /W array.
     *
     * @param array<int,int> $usedGlyphs [glyphId => codePoint]
     */
    private static function buildWidthsArray(TrueTypeFont $font, array $usedGlyphs): PdfArray
    {
        if ($usedGlyphs === []) {
            return new PdfArray([]);
        }

        ksort($usedGlyphs);
        $entries = [];
        $groupStart = null;
        $groupWidths = [];
        $prev = -2;

        foreach (array_keys($usedGlyphs) as $gid) {
            if ($gid !== $prev + 1) {
                if ($groupStart !== null) {
                    $entries[] = new PdfInteger($groupStart);
                    $entries[] = new PdfArray(array_map(static fn ($w) => new PdfInteger($w), $groupWidths));
                }

                $groupStart = $gid;
                $groupWidths = [];
            }

            $groupWidths[] = $font->toPdfUnits($font->getAdvanceWidth($gid));
            $prev = $gid;
        }

        if ($groupStart !== null) {
            $entries[] = new PdfInteger($groupStart);
            $entries[] = new PdfArray(array_map(static fn ($w) => new PdfInteger($w), $groupWidths));
        }

        return new PdfArray($entries);
    }
}
