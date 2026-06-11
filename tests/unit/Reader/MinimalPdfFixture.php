<?php

declare(strict_types=1);

namespace PhpPdf\Reader;

use PhpPdf\Object\PdfVersion;

/**
 * Builds an in-memory minimal PDF and a PdfReadDocument from it.
 * The minimal document has three objects: Catalog, Pages, one Page.
 */
trait MinimalPdfFixture
{
    private static function minimalPdfContent(): string
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] >>\nendobj\n",
        ];

        $offsets = [];

        foreach ($objs as $i => $obj) {
            $offsets[$i + 1] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);

        $xref = "xref\n0 4\n";
        $xref .= "0000000000 65535 f \n";

        for ($i = 1; $i <= 3; $i++) {
            $xref .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $xref .= "trailer\n<< /Size 4 /Root 1 0 R >>\n";
        $xref .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $header . $body . $xref;
    }

    private static function createMinimalDocument(): PdfReadDocument
    {
        $content = self::minimalPdfContent();
        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }
}
