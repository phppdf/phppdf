<?php

declare(strict_types=1);

namespace PhpPdf\Object;

use RuntimeException;

/**
 * A ToUnicode CMap stream mapping glyph codes to Unicode code points.
 *
 * Required for text extraction and search in composite (Type 0) fonts.
 * The $usedGlyphs array maps glyph IDs to Unicode code points. The generated
 * CMap is split into blocks of at most 100 entries (PDF spec limit) and
 * compressed with FlateDecode.
 * Without a ToUnicode CMap, PDF viewers cannot copy or search text rendered
 * with a custom font.
 */
final class PdfToUnicodeCMap extends PdfStream
{
    /** @param array<int, int> $usedGlyphs [glyphId => codePoint] */
    public function __construct(array $usedGlyphs)
    {
        $content = $this->buildCMap($usedGlyphs);

        $compressed = gzcompress($content);

        if ($compressed === false) {
            // @codeCoverageIgnoreStart
            throw new RuntimeException('Failed to compress ToUnicode CMap content.');
            // @codeCoverageIgnoreEnd
        }

        parent::__construct(
            new PdfDictionary([
                'Filter' => new PdfName('FlateDecode'),
            ]),
            new PdfRawStreamData($compressed),
        );
    }

    /** @param array<int, int> $usedGlyphs [glyphId => codePoint] */
    private function buildCMap(array $usedGlyphs): string
    {
        $header = implode("\n", [
            '/CIDInit /ProcSet findresource begin',
            '12 dict begin',
            'begincmap',
            '/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def',
            '/CMapName /Adobe-Identity-UCS def',
            '/CMapType 2 def',
            '1 begincodespacerange',
            '<0000> <FFFF>',
            'endcodespacerange',
        ]);

        $lines = array_map(
            static fn ($gid, $cp) => sprintf('<%04X> <%04X>', $gid, $cp),
            array_keys($usedGlyphs),
            array_values($usedGlyphs),
        );

        $blocks = '';

        foreach (array_chunk($lines, 100) as $chunk) {
            $blocks .= count($chunk) . " beginbfchar\n"
                . implode("\n", $chunk) . "\n"
                . "endbfchar\n";
        }

        return $header . "\n" . $blocks
            . "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend";
    }
}
