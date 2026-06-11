<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfDocumentReader;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfDocumentReader;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentReader::class)]
#[CoversMethod(PdfDocumentReader::class, 'openEncrypted')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class OpenEncryptedTest extends TestCase
{
    #[Test]
    public function throwsForNonExistentFile(): void
    {
        // Arrange / Act / Assert
        $this->expectException(PdfReadException::class);
        PdfDocumentReader::openEncrypted('/nonexistent/file.pdf');
    }

    #[Test]
    public function throwsWhenTrailerHasNoEncryptEntry(): void
    {
        // Arrange — plain PDF, no /Encrypt in trailer
        $tmp = self::writeTempPdf(self::minimalPdf());

        try {
            // Act / Assert — no /Encrypt → encryptDictNotFound
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsWhenEncryptEntryIsNotIndirectRef(): void
    {
        // Arrange — /Encrypt is a direct integer (not an indirect ref)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 3\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        // /Encrypt is a direct dict value (integer), not a ref
        $content .= "trailer\n<< /Size 3 /Root 1 0 R /Encrypt 42 >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsWhenEncryptObjectNotInXref(): void
    {
        // Arrange — /Encrypt 3 0 R but obj 3 does not appear in the xref at all
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        // xref covers only obj 0–2; obj 3 is not registered
        $content .= "xref\n0 3\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= "trailer\n<< /Size 3 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — obj 3 not in xref → encryptDictNotFound
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsWhenEncryptXrefEntryIsNotNormal(): void
    {
        // Arrange — /Encrypt 3 0 R exists in xref but with type 'f' (free entry)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        // obj 3: free entry (type 'f')
        $content .= "0000000000 00001 f \n";
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — type='f' → encryptDictNotFound
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsWhenEncryptObjectOffsetIsAtEof(): void
    {
        // Arrange — /Encrypt 3 0 R in xref at offset beyond file end
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        // obj 3: normal entry but offset 999999 is well beyond EOF
        $content .= "0000999999 00000 n \n";
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — seeks to EOF → parseIndirectObject returns null → encryptDictNotFound
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsWhenEncryptObjectIsNotDictionary(): void
    {
        // Arrange — /Encrypt 3 0 R but obj 3 is an integer, not a dict
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n42\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — integer body → not a PdfDictionary → encryptDictNotFound
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsForUnsupportedEncryptionScheme(): void
    {
        // Arrange — /Encrypt dict exists but uses /Filter /Adobe.FDF (not Standard)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Filter /AdobeExt /V 1 /R 2 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — non-Standard filter
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsForUnsupportedEncryptionVersion(): void
    {
        // Arrange — /Encrypt with /Filter /Standard but V=2/R=3 (not V=4/R=4)
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Filter /Standard /V 2 /R 3 >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = self::writeTempPdf($content);

        try {
            // Act / Assert — V=2/R=3 not supported (only V=4/R=4)
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp);
        } finally {
            unlink($tmp);
        }
    }

    private static function writeTempPdf(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_enc_');
        file_put_contents($tmp, $content);

        return $tmp;
    }

    private static function minimalPdf(): string
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $objs = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n",
        ];

        $offsets = [];

        foreach ($objs as $i => $obj) {
            $offsets[$i + 1] = strlen($header) + strlen($body);
            $body .= $obj;
        }

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 3\n";
        $content .= "0000000000 65535 f \n";

        for ($i = 1; $i <= 2; $i++) {
            $content .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $content .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $content;
    }
}
