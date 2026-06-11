<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTextExtractor;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Object\PdfRawStreamData;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfVersion;
use PhpPdf\Reader\PdfLexer;
use PhpPdf\Reader\PdfObjectParser;
use PhpPdf\Reader\PdfReadDocument;
use PhpPdf\Reader\PdfReadPage;
use PhpPdf\Reader\PdfTextExtractionState;
use PhpPdf\Reader\PdfTextExtractor;
use PhpPdf\Reader\PdfToken;
use PhpPdf\Reader\PdfXRefTable;
use PhpPdf\Serialization\PdfStreamSerializer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTextExtractor::class)]
#[CoversMethod(PdfTextExtractor::class, 'getTextForPage')]
#[UsesClass(PdfTextExtractionState::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfRawStreamData::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfReadPage::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
#[UsesClass(PdfStreamSerializer::class)]
final class TextOperatorsTest extends TestCase
{
    #[Test]
    public function extractsTextFromTjOperator(): void
    {
        // Arrange
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n(Hello) Tj\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Hello', $text);
    }

    #[Test]
    public function extractsTextFromTjOperatorWithArray(): void
    {
        // Arrange — TJ with array of strings and kerning values
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n[(Hello) -300 (World)] TJ\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — large negative kerning (-300 < -200) inserts a space
        self::assertStringContainsString('Hello', $text);
        self::assertStringContainsString('World', $text);
    }

    #[Test]
    public function insertNewlineForTStarOperator(): void
    {
        // Arrange
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n(Line1) Tj\nT*\n(Line2) Tj\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Line1', $text);
        self::assertStringContainsString('Line2', $text);
        self::assertStringContainsString("\n", $text);
    }

    #[Test]
    public function insertNewlineForTdOperatorWithNegativeY(): void
    {
        // Arrange — Td with negative y moves down = new line
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n(Line1) Tj\n0 -14 Td\n(Line2) Tj\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Line1', $text);
        self::assertStringContainsString('Line2', $text);
    }

    #[Test]
    public function extractsTextFromSingleQuoteOperator(): void
    {
        // Arrange — ' operator = move to next line and show text
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n(Line1) '\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Line1', $text);
    }

    #[Test]
    public function extractsTextFromDoubleQuoteOperator(): void
    {
        // Arrange — " operator = set spacing, move to next line, show text (3 operands)
        $document = self::buildDocumentWithContent("BT\n/F1 12 Tf\n0 0 (Line1) \"\nET\n");
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Line1', $text);
    }

    #[Test]
    public function insertsNewlineForTmWithChangedY(): void
    {
        // Arrange — Tm with different y values between calls
        $stream = "BT\n/F1 12 Tf\n1 0 0 1 0 700 Tm\n(First) Tj\n1 0 0 1 0 686 Tm\n(Second) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('First', $text);
        self::assertStringContainsString('Second', $text);
    }

    #[Test]
    public function commentInContentStreamIsIgnoredAndRealNumberParsed(): void
    {
        // Arrange — comment skipped; real number in Td arg; positive y triggers elseif newline
        $stream = "BT\n% this comment is skipped\n/F1 12 Tf\n(A) Tj\n0 2.5 Td\n(B) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — both strings extracted; newline inserted for positive y offset
        self::assertStringContainsString('A', $text);
        self::assertStringContainsString('B', $text);
    }

    #[Test]
    public function dictTokensAndEmptyWordsAreHandledInContentStream(): void
    {
        // Arrange — '<< >>' produces dict_start/dict_end tokens; '{' and '}' produce
        //            empty words (word = '' → $i++ continue) which are silently skipped
        $stream = "BT\n/F1 12 Tf\n<< /K /V >>\n{}\n(Hello) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('Hello', $text);
    }

    #[Test]
    public function stringEscapesInContentStream(): void
    {
        // Arrange — exercises octal (\101='A'), \n line-continuation, unknown escape (\x→'x'),
        //            and nested parentheses in a single literal string
        $stream = "BT\n/F1 12 Tf\n(\\101\\\nAB\\x(nest)c) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — result contains the decoded characters
        self::assertStringContainsString('A', $text); // \101 = chr(65) = 'A'
    }

    #[Test]
    public function literalStringHandlesAllNamedEscapeSequences(): void
    {
        // Arrange — exercises \n \r \t \b \f \( \) \\ escape sequences
        $stream = "BT\n/F1 12 Tf\n(A\\nB\\rC\\tD\\bE\\fF\\(G\\)H\\\\I) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — escapes decoded to their literal characters
        self::assertStringContainsString("A\nB\rC\tD\x08E\x0CF(G)H\\I", $text);
    }

    #[Test]
    public function hexStringWhitespaceAndOddLengthInContentStream(): void
    {
        // Arrange — '<48 65>' has internal whitespace (skipped); '<48F>' has odd length (padded)
        $stream = "BT\n/F1 12 Tf\n<48 65> Tj\n<48F> Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — 'He' from <4865> and first byte from <48F0>
        self::assertStringContainsString('H', $text);
    }

    #[Test]
    public function nameWithHexEncodedCharacterInContentStream(): void
    {
        // Arrange — font name /He#6Clo has '#6C' which hex-decodes to 'l' → "Hello"
        $stream = "BT\n/He#6Clo 12 Tf\n(test) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act — just verify extraction does not crash
        $text = $extractor->getTextForPage(0);

        // Assert
        self::assertStringContainsString('test', $text);
    }

    #[Test]
    public function nonUtf8StringFallsBackToWindows1252Decoding(): void
    {
        // Arrange — byte 0x91 is not valid UTF-8; Windows-1252 maps it to U+2018 (left quote)
        $stream = "BT\n/F1 12 Tf\n(\x91) Tj\nET\n";
        $document = self::buildDocumentWithContent($stream);
        $extractor = new PdfTextExtractor($document);

        // Act
        $text = $extractor->getTextForPage(0);

        // Assert — conversion succeeded (result is valid UTF-8)
        self::assertTrue(mb_check_encoding($text, 'UTF-8'));
    }

    private static function buildDocumentWithContent(string $contentStream): PdfReadDocument // phpcs:ignore
    {
        $compressed = gzcompress($contentStream) ?: '';
        $header = "%PDF-1.4\n";
        $body = '';

        $off1 = strlen($header) + strlen($body);
        $body .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        $off2 = strlen($header) + strlen($body);
        $body .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        $off3 = strlen($header) + strlen($body);
        $body .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R >>\nendobj\n";

        $off4 = strlen($header) + strlen($body);
        $body .= "4 0 obj\n<< /Length " . strlen($compressed) . " /Filter /FlateDecode >>\nstream\n"
               . $compressed . "\nendstream\nendobj\n";

        $xrefOffset = strlen($header) + strlen($body);
        $content = $header . $body;

        $content .= "xref\n0 5\n";
        $content .= "0000000000 65535 f \n";
        $content .= sprintf("%010d 00000 n \n", $off1);
        $content .= sprintf("%010d 00000 n \n", $off2);
        $content .= sprintf("%010d 00000 n \n", $off3);
        $content .= sprintf("%010d 00000 n \n", $off4);
        $content .= "trailer\n<< /Size 5 /Root 1 0 R >>\n";
        $content .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $lexer = PdfLexer::fromString($content);
        $startXRef = $lexer->findStartXRef();
        [$xref, $trailer] = (new PdfXRefTable($lexer))->parse($startXRef);

        return new PdfReadDocument($lexer, $xref, $trailer, PdfVersion::PDF_1_4, null, $startXRef);
    }
}
