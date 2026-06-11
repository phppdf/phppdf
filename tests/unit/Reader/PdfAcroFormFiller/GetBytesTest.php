<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAcroFormFiller;

use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfNull;
use PhpPdf\Reader\MinimalPdfFixture;
use PhpPdf\Reader\PdfAcroFormFiller;
use PhpPdf\Reader\PdfFormField;
use PhpPdf\Reader\PdfFormFieldType;
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

#[CoversClass(PdfAcroFormFiller::class)]
#[CoversMethod(PdfAcroFormFiller::class, 'getBytes')]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfFormField::class)]
#[UsesClass(PdfFormFieldType::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfNull::class)]
#[UsesClass(PdfLexer::class)]
#[UsesClass(PdfObjectParser::class)]
#[UsesClass(PdfReadDocument::class)]
#[UsesClass(PdfToken::class)]
#[UsesClass(PdfXRefTable::class)]
final class GetBytesTest extends TestCase
{
    use MinimalPdfFixture;

    #[Test]
    public function returnsOriginalBytesWhenNoModifications(): void
    {
        // Arrange
        $originalBytes = self::minimalPdfContent();
        $document = self::createMinimalDocument();
        $filler = new PdfAcroFormFiller($document, $originalBytes);

        // Act
        $result = $filler->getBytes();

        // Assert
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function setTextOnReadOnlyFieldDoesNothing(): void
    {
        // Arrange
        $originalBytes = self::minimalPdfContent();
        $document = self::createMinimalDocument();
        $filler = new PdfAcroFormFiller($document, $originalBytes);
        $field = new PdfFormField(
            objectNumber: 0,
            generationNumber: 0,
            name: 'name',
            fullName: 'name',
            type: PdfFormFieldType::Text,
            value: null,
            readOnly: true,
        );

        // Act
        $filler->setText($field, 'John');
        $result = $filler->getBytes();

        // Assert — no modification means original bytes returned
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function setCheckedOnReadOnlyFieldDoesNothing(): void
    {
        // Arrange
        $originalBytes = self::minimalPdfContent();
        $document = self::createMinimalDocument();
        $filler = new PdfAcroFormFiller($document, $originalBytes);
        $field = new PdfFormField(
            objectNumber: 0,
            generationNumber: 0,
            name: 'check',
            fullName: 'check',
            type: PdfFormFieldType::Button,
            value: false,
            readOnly: true,
        );

        // Act
        $filler->setChecked($field, true);
        $result = $filler->getBytes();

        // Assert
        self::assertSame($originalBytes, $result);
    }

    #[Test]
    public function setChoiceOnNonChoiceFieldDoesNothing(): void
    {
        // Arrange
        $originalBytes = self::minimalPdfContent();
        $document = self::createMinimalDocument();
        $filler = new PdfAcroFormFiller($document, $originalBytes);
        $field = new PdfFormField(
            objectNumber: 0,
            generationNumber: 0,
            name: 'txt',
            fullName: 'txt',
            type: PdfFormFieldType::Text,
            value: null,
        );

        // Act — setChoice only accepts Choice type
        $filler->setChoice($field, 'option');
        $result = $filler->getBytes();

        // Assert
        self::assertSame($originalBytes, $result);
    }
}
