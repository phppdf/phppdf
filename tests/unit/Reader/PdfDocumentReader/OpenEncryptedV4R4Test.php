<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfDocumentReader;

use PhpPdf\Encryption\PdfDecryptionHandler;
use PhpPdf\Encryption\PdfEncryptionConfig;
use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Encryption\PdfPermissions;
use PhpPdf\Encryption\PdfStandardSecurityHandler;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfString;
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
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfEncryptionConfig::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfPermissions::class)]
#[UsesClass(PdfStandardSecurityHandler::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfDecryptionHandler::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class OpenEncryptedV4R4Test extends TestCase
{
    #[Test]
    public function throwsWrongPasswordForV4R4WithFakeData(): void
    {
        // Arrange — fake V=4/R=4 PDF; authenticate will fail → wrongPassword
        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_v4r4_');
        file_put_contents($tmp, self::buildFakeEncryptedPdf());

        try {
            // Act / Assert — passes V/R check, calls extractFileId, then fails auth
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp, '');
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function extractFileIdReturnsEmptyWhenNoIdInTrailer(): void
    {
        // Arrange — V=4/R=4 PDF without /ID in trailer;
        // extractFileId() returns '' for the empty PdfArray branch
        $stub32 = str_repeat("\x00", 32);
        $stub16 = str_repeat("\x00", 16);

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n"
               . "<< /Filter /Standard /V 4 /R 4 /P -3904 "
               . "/O (" . addslashes($stub32) . ") "
               . "/U (" . addslashes($stub32) . ") "
               . "/OE (" . addslashes($stub32) . ") "
               . "/UE (" . addslashes($stub32) . ") "
               . "/Perms (" . addslashes($stub16) . ") "
               . "/CF << /StdCF << /CFM /AESV2 >> >> "
               . "/StmF /StdCF /StrF /StdCF >>\n"
               . "endobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        // No /ID entry in trailer — extractFileId checks !$idEntry instanceof PdfArray → return ''
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_noid_');
        file_put_contents($tmp, $content);

        try {
            // Act / Assert — extractFileId returns '', auth fails → wrongPassword
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp, '');
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function extractFileIdReturnsEmptyWhenIdFirstElementIsNotString(): void
    {
        // Arrange — V=4/R=4 PDF with /ID [42 43] (integers, not strings);
        // extractFileId() hits the false branch of "$first instanceof PdfString"
        $stub32 = str_repeat("\x00", 32);
        $stub16 = str_repeat("\x00", 16);

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n"
               . "<< /Filter /Standard /V 4 /R 4 /P -3904 "
               . "/O (" . addslashes($stub32) . ") "
               . "/U (" . addslashes($stub32) . ") "
               . "/OE (" . addslashes($stub32) . ") "
               . "/UE (" . addslashes($stub32) . ") "
               . "/Perms (" . addslashes($stub16) . ") "
               . "/CF << /StdCF << /CFM /AESV2 >> >> "
               . "/StmF /StdCF /StrF /StdCF >>\n"
               . "endobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        // /ID contains integers, not strings — first element is PdfInteger, not PdfString
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R /ID [42 43] >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_intid_');
        file_put_contents($tmp, $content);

        try {
            // Act / Assert — extractFileId returns '', auth fails → wrongPassword
            $this->expectException(PdfReadException::class);
            PdfDocumentReader::openEncrypted($tmp, '');
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function openEncryptedSucceedsWithCorrectPassword(): void
    {
        // Arrange — build a real V=4/R=4 encrypted PDF with valid O/U values so
        // authentication succeeds (covers lines 103-105).
        // Use hex strings (<hexdata>) for O, U, and /ID so arbitrary binary bytes
        // do not break the PDF literal-string parser.
        $fileId = "\xDE\xAD\xBE\xEF\xCA\xFE\xBA\xBE\x01\x02\x03\x04\x05\x06\x07\x08";

        $config = (new PdfEncryptionConfig())->userPassword('secret')->ownerPassword('admin');
        $handler = new PdfStandardSecurityHandler($config, $fileId);
        $encryptDict = $handler->buildEncryptionDictionary();

        // Embed O/U as hex strings so no binary byte can corrupt the PDF syntax.
        $oValue = $encryptDict->get('O');
        $uValue = $encryptDict->get('U');
        $pValue = $encryptDict->get('P');
        self::assertInstanceOf(PdfHexString::class, $oValue);
        self::assertInstanceOf(PdfHexString::class, $uValue);
        self::assertInstanceOf(PdfInteger::class, $pValue);
        $Ohex = strtoupper(bin2hex($oValue->getBinary()));
        $Uhex = strtoupper(bin2hex($uValue->getBinary()));
        $P = $pValue->getValue();
        $fileIdHex = strtoupper(bin2hex($fileId));

        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n"
               . "<< /Filter /Standard /V 4 /R 4 /P {$P} "
               . "/O <{$Ohex}> "
               . "/U <{$Uhex}> "
               . "/CF << /StdCF << /CFM /AESV2 >> >> "
               . "/StmF /StdCF /StrF /StdCF >>\n"
               . "endobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 4\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R"
                  . " /ID [<{$fileIdHex}> <{$fileIdHex}>] >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_ok_');
        file_put_contents($tmp, $content);

        try {
            // Act — authentication should succeed
            $document = PdfDocumentReader::openEncrypted($tmp, 'secret');

            // Assert — returns PdfReadDocument with encryption context set (lines 103-105)
            self::assertInstanceOf(PdfReadDocument::class, $document);
            self::assertNotNull($document->getDecryptionContext());
        } finally {
            unlink($tmp);
        }
    }

    #[Test]
    public function throwsUnsupportedEncryptionWhenVAndRAreNotIntegers(): void
    {
        // Arrange — /V and /R are names instead of integers, so they fall back to 0
        // and fail the V=4/R=4 check (covers the "default 0" branches).
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Filter /Standard /V /Foo /R /Bar >>\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;
        $content .= "xref\n0 4\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $tmp = tempnam(sys_get_temp_dir(), 'phppdf_norvint_');
        file_put_contents($tmp, $content);

        try {
            // Act / Assert — V/R default to 0, fail the V=4/R=4 check
            $this->expectException(PdfReadException::class);
            $this->expectExceptionMessage('V=0/R=0');
            PdfDocumentReader::openEncrypted($tmp, '');
        } finally {
            unlink($tmp);
        }
    }

    /**
     * Builds a PDF with V=4/R=4 encrypt dict (fake encryption data).
     * The dict has the required fields to pass the V/R check, and the trailer
     * has an /ID array. PdfDecryptionHandler::authenticate will fail with fake
     * data, resulting in a wrongPassword exception — but extractFileId() is
     * exercised along the way.
     */
    private static function buildFakeEncryptedPdf(): string
    {
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\n";

        // obj 3: fake encrypt dict with V=4, R=4, Filter=Standard
        // We provide stub 32-byte values for O, U, OE, UE and 16-byte Perms.
        $stub32 = str_repeat("\x00", 32);
        $stub16 = str_repeat("\x00", 16);
        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n"
               . "<< /Filter /Standard /V 4 /R 4 /P -3904 "
               . "/O (" . addslashes($stub32) . ") "
               . "/U (" . addslashes($stub32) . ") "
               . "/OE (" . addslashes($stub32) . ") "
               . "/UE (" . addslashes($stub32) . ") "
               . "/Perms (" . addslashes($stub16) . ") "
               . "/CF << /StdCF << /CFM /AESV2 >> >> "
               . "/StmF /StdCF /StrF /StdCF >>\n"
               . "endobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 4\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        // Trailer with /Encrypt and /ID (to exercise extractFileId)
        $content .= "trailer\n<< /Size 4 /Root 1 0 R /Encrypt 3 0 R"
                  . " /ID [(\xAB\xCD\xEF\x01) (\xAB\xCD\xEF\x01)] >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        return $content;
    }
}
