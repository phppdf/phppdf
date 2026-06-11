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
#[CoversMethod(PdfDocumentBuilder::class, 'footer')]
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
final class FooterTest extends TestCase
{
    #[Test]
    public function footerReturnsSelf(): void
    {
        // Arrange
        $builder = new PdfDocumentBuilder();

        // Act
        $result = $builder->footer(static fn () => null);

        // Assert
        self::assertSame($builder, $result);
    }

    #[Test]
    public function footerTemplateIsAppliedDuringBuild(): void
    {
        // Arrange
        $footerCalled = false;

        // Act
        $document = (new PdfDocumentBuilder())
            ->footer(
                static function (PdfContentStreamBuilder $stream, int $page, int $total) use (&$footerCalled): void {
                    $footerCalled = true;
                },
            )
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
        self::assertTrue($footerCalled);
    }

    #[Test]
    public function headerAndFooterBothApplied(): void
    {
        // Arrange
        $calls = [];

        // Act — both header and footer set: covers both branches in applyPageTemplate,
        // producing a three-element Contents array (header + body + footer).
        $document = (new PdfDocumentBuilder())
            ->header(static function (PdfContentStreamBuilder $s, int $p, int $t) use (&$calls): void {
                $calls[] = 'header';
            })
            ->footer(static function (PdfContentStreamBuilder $s, int $p, int $t) use (&$calls): void {
                $calls[] = 'footer';
            })
            ->page(static fn () => null)
            ->build();

        // Assert
        self::assertInstanceOf(PdfDocument::class, $document);
        self::assertContains('header', $calls);
        self::assertContains('footer', $calls);
    }
}
