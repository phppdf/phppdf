<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

/**
 * PDF/A conformance levels supported by PdfDocumentBuilder::conformTo().
 *
 * The level is encoded as a two-character string: the part number followed by
 * the conformance letter. Use this enum when calling conformTo() to mark a
 * document for archival and to have the correct XMP metadata inserted
 * automatically.
 *
 * Commonly used levels:
 *   PdfA1b - ISO 19005-1, basic conformance (no tagged content required)
 *   PdfA2b - ISO 19005-2, basic; based on PDF 1.7, recommended for new work
 *   PdfA3b - ISO 19005-3, basic; allows embedded files of any format
 *
 * Note: 'a' levels (accessible) additionally require tagged PDF structure
 * trees, which this library does not yet produce. 'u' levels require Unicode
 * mappings for all glyphs (custom embedded fonts only).
 */
enum PdfAConformance: string
{
    /** ISO 19005-1, Level B - basic visual reproducibility; based on PDF 1.4. */
    case PdfA1b = '1b';

    /** ISO 19005-1, Level A - accessible; requires tagged PDF structure (not yet produced by this library). */
    case PdfA1a = '1a';

    /** ISO 19005-2, Level B - basic visual reproducibility; based on PDF 1.7; recommended for new work. */
    case PdfA2b = '2b';

    /** ISO 19005-2, Level A - accessible; requires tagged PDF structure (not yet produced by this library). */
    case PdfA2a = '2a';

    /** ISO 19005-2, Level U - Unicode; requires Unicode mappings for all glyphs (custom embedded fonts only). */
    case PdfA2u = '2u';

    /** ISO 19005-3, Level B - basic visual reproducibility; based on PDF 1.7; allows embedded files of any format. */
    case PdfA3b = '3b';

    /** ISO 19005-3, Level A - accessible; requires tagged PDF structure (not yet produced by this library). */
    case PdfA3a = '3a';

    /** ISO 19005-3, Level U - Unicode; requires Unicode mappings for all glyphs (custom embedded fonts only). */
    case PdfA3u = '3u';

    /**
     * Returns the ISO 19005 part number (1, 2, or 3).
     *
     * Corresponds to the PDF version the standard is based on:
     * part 1 → PDF 1.4, part 2 → PDF 1.7, part 3 → PDF 1.7.
     */
    public function part(): int
    {
        return (int) $this->value[0];
    }

    /**
     * Returns the conformance level letter in upper case: 'B', 'A', or 'U'.
     *
     * - B (Basic) - ensures visual reproducibility; no structure requirements.
     * - A (Accessible) - superset of B; additionally requires tagged PDF.
     * - U (Unicode) - superset of B; additionally requires Unicode mappings.
     */
    public function conformanceLevel(): string
    {
        return strtoupper($this->value[1]);
    }
}
