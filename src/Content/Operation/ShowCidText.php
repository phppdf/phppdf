<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation;

use PhpPdf\Object\PdfVersion;
use PhpPdf\Serialization\PdfContentStreamSerializer;

/**
 * Writes a hex-encoded glyph-ID string followed by the Tj operator.
 *
 * Used for composite (Type0 / Identity-H) fonts where each character is
 * represented as a two-byte glyph ID rather than a single encoded byte.
 * The operand is a PDF hex string: <0041 0042 ...>.
 *
 * Instances are created by PdfContentStreamBuilder::showText() when the
 * current font was registered with PdfPageBuilder::useEmbeddedFont().
 */
final class ShowCidText implements PdfContentOperation
{
    /** @param array<int> $glyphIds */
    public function __construct(private readonly array $glyphIds)
    {
    }

    /** @return array<int> */
    public function getGlyphIds(): array
    {
        return $this->glyphIds;
    }

    public function minimumVersion(): PdfVersion
    {
        return PdfVersion::PDF_1_0;
    }

    public function serialize(PdfContentStreamSerializer $serializer): void
    {
        $hex = '';

        foreach ($this->glyphIds as $gid) {
            $hex .= sprintf('%04X', $gid);
        }

        $serializer->writeLine("<{$hex}> Tj");
    }
}
