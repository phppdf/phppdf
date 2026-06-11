<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfDocumentBuilder;

use PhpPdf\Builder\PdfDocumentBuilder;
use PhpPdf\Builder\PdfPageBuilder;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Document\PdfDocument;
use PhpPdf\Object\PdfArray;
use PhpPdf\Object\PdfContentStream;
use PhpPdf\Object\PdfContentStreamData;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfHexString;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfRawObject;
use PhpPdf\Object\PdfRectangle;
use PhpPdf\Object\PdfStream;
use PhpPdf\Object\PdfString;
use PhpPdf\Signing\PdfSignatureConfig;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'prepareForSigning')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfHexString::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfRawObject::class)]
#[UsesClass(PdfRectangle::class)]
#[UsesClass(PdfSignatureConfig::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(PdfString::class)]
#[UsesClass(SvgRenderer::class)]
final class PrepareForSigningTest extends TestCase
{
    #[Test]
    public function prepareForSigningReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->prepareForSigning(new PdfSignatureConfig());

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function prepareForSigningWithNoPagesSkipsSignatureStructure(): void
    {
        // Arrange / Act — signature config set but no pages: the condition
        // `count($pageRefs) > 0` is false, so buildSignatureStructure is not called.
        $document = (new PdfDocumentBuilder())
            ->prepareForSigning(new PdfSignatureConfig())
            ->build();

        // Assert — document builds without error
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function prepareForSigningWithPageBuildsSignatureStructure(): void
    {
        // Arrange / Act — a page is present so buildSignatureStructure runs
        $document = (new PdfDocumentBuilder())
            ->prepareForSigning(new PdfSignatureConfig())
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }

    #[Test]
    public function prepareForSigningWithAllOptionalFieldsBuildsDocument(): void
    {
        // Arrange / Act — all optional signature fields cover the null-check branches
        $config = (new PdfSignatureConfig())
            ->name('Jane Smith')
            ->reason('Document approval')
            ->location('Amsterdam')
            ->contactInfo('jane@example.com');

        $document = (new PdfDocumentBuilder())
            ->prepareForSigning($config)
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
    }
}
