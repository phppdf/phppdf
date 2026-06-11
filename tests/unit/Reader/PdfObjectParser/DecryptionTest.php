<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfObjectParser;

use PhpPdf\Encryption\PdfEncryptionContext;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfBoolean;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfReal;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Reader\Exception\PdfReadException;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfObjectParser::class)]
#[CoversMethod(PdfObjectParser::class, 'parseObject')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfBoolean::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfEncryptionContext::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfReal::class)]
#[UsesClass(PdfReadException::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfToken::class)]
final class DecryptionTest extends TestCase
{
    #[Test]
    public function decryptsStringWithEncryptionContext(): void
    {
        // Arrange — encrypt a known plaintext and feed the ciphertext as a hex string
        $key = str_repeat('k', 16);
        $ctx = new PdfEncryptionContext($key);
        $plaintext = 'secret';
        $ciphertext = $ctx->encrypt($plaintext, 1, 0);

        // Hex strings handle arbitrary binary bytes without escaping
        $hexStr = '<' . bin2hex($ciphertext) . '>';
        $parser = new PdfObjectParser(PdfLexer::fromString($hexStr), $ctx, 1, 0);

        // Act
        $obj = $parser->parseObject();

        // Assert — decryption restored the original plaintext
        self::assertInstanceOf(PdfString::class, $obj);
        self::assertSame($plaintext, $obj->getValue());
    }

    #[Test]
    public function decryptsStreamWithEncryptionContext(): void
    {
        // Arrange — encrypt stream data; exact /Length prevents endstream confusion
        $key = str_repeat('k', 16);
        $ctx = new PdfEncryptionContext($key);
        $plaintext = 'stream data';
        $ciphertext = $ctx->encrypt($plaintext, 2, 0);

        $content = "<< /Length " . strlen($ciphertext) . " >>\nstream\n"
                 . $ciphertext . "\nendstream";
        $parser = new PdfObjectParser(PdfLexer::fromString($content), $ctx, 2, 0);

        // Act
        $obj = $parser->parseObject();

        // Assert
        self::assertInstanceOf(PdfStream::class, $obj);
    }
}
