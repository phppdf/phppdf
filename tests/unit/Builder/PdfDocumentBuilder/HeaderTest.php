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
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfInteger;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PhpPdf\Object\PdfStream;
use PhpPdf\Svg\SvgRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentBuilder::class)]
#[CoversMethod(PdfDocumentBuilder::class, 'header')]
#[UsesClass(PdfDocument::class)]
#[UsesClass(PdfArray::class)]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PdfContentStreamBuilder::class)]
#[UsesClass(PdfContentStreamData::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfInteger::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
#[UsesClass(PdfPageBuilder::class)]
#[UsesClass(PdfStream::class)]
#[UsesClass(SvgRenderer::class)]
final class HeaderTest extends TestCase
{
    #[Test]
    public function headerReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->header(static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function headerTemplateIsAppliedDuringBuild(): void
    {
        // Arrange
        $headerCalled = false;

        // Act
        $document = (new PdfDocumentBuilder())
            ->header(
                static function (PdfContentStreamBuilder $stream, int $page, int $total) use (&$headerCalled): void {
                    $headerCalled = true;
                },
            )
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
        self::assertTrue($headerCalled);
    }
}
