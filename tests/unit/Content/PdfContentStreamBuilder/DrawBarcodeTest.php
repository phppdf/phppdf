<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Barcode\Code128;
use PhpPdf\Barcode\LinearBarcode;
use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\AppendRectangle;
use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\Operation\EndText;
use PhpPdf\Content\Operation\FillPath;
use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Content\Operation\SaveGraphicsState;
use PhpPdf\Content\Operation\SetFont;
use PhpPdf\Content\Operation\SetNonStrokingGray;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\Operation\ShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawBarcode')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendRectangle::class)]
#[UsesClass(BeginText::class)]
#[UsesClass(Code128::class)]
#[UsesClass(Color::class)]
#[UsesClass(ColorType::class)]
#[UsesClass(EndText::class)]
#[UsesClass(FillPath::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(RestoreGraphicsState::class)]
#[UsesClass(SaveGraphicsState::class)]
#[UsesClass(SetFont::class)]
#[UsesClass(SetNonStrokingGray::class)]
#[UsesClass(SetTextMatrix::class)]
#[UsesClass(ShowText::class)]
#[UsesClass(Type1FontMetrics::class)]
final class DrawBarcodeTest extends TestCase
{
    #[Test]
    public function drawBarcodeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        $barcode = Code128::encode('HELLO');
        self::assertSame($builder, $builder->drawBarcode($barcode, x: 72.0, y: 600.0, height: 40.0));
    }

    #[Test]
    public function drawBarcodeWithoutTextDoesNotAddTextOperations(): void
    {
        $builder = new PdfContentStreamBuilder();
        $barcode = Code128::encode('TEST');

        // No fontName / metrics provided → text branch skipped
        $builder->drawBarcode($barcode, x: 0.0, y: 0.0, height: 20.0, fontName: '');
        $ops = $builder->build()->getOperations();

        $types = array_map(static fn ($op) => $op::class, $ops);
        self::assertNotContains(BeginText::class, $types);
        self::assertNotContains(EndText::class, $types);
    }

    #[Test]
    public function drawBarcodeWithTextAddsTextOperations(): void
    {
        $builder = new PdfContentStreamBuilder();
        $barcode = Code128::encode('ABC');
        $metrics = Type1FontMetrics::helvetica();

        $builder->drawBarcode(
            $barcode,
            x: 72.0,
            y: 600.0,
            height: 40.0,
            moduleWidth: 1.2,
            quietZone: 10.0,
            fontName: 'F1',
            fontSize: 8.0,
            metrics: $metrics,
        );
        $ops = $builder->build()->getOperations();

        $types = array_map(static fn ($op) => $op::class, $ops);
        self::assertContains(BeginText::class, $types);
        self::assertContains(EndText::class, $types);
        self::assertContains(SetFont::class, $types);
        self::assertContains(ShowText::class, $types);
    }

    #[Test]
    public function drawBarcodeDrawsBarsAsFilledRectangles(): void
    {
        $builder = new PdfContentStreamBuilder();
        $barcode = Code128::encode('A');

        $builder->drawBarcode($barcode, x: 0.0, y: 0.0, height: 20.0);
        $ops = $builder->build()->getOperations();

        $types = array_map(static fn ($op) => $op::class, $ops);
        self::assertContains(AppendRectangle::class, $types);
        self::assertContains(FillPath::class, $types);
    }

    #[Test]
    public function drawBarcodeWithoutTextWhenBarcodeTextIsEmpty(): void
    {
        // Use a LinearBarcode whose getText() returns '' to exercise that branch.
        $barcode = new class implements LinearBarcode {
            /** @return list<int> */
            public function getBars(): array
            {
                return [1, 1, 1];
            }

            public function getText(): string
            {
                return '';
            }
        };

        $builder = new PdfContentStreamBuilder();
        $builder->drawBarcode($barcode, x: 0.0, y: 0.0, height: 10.0, fontName: 'F1');
        $ops = $builder->build()->getOperations();
        $types = array_map(static fn ($op) => $op::class, $ops);
        self::assertNotContains(BeginText::class, $types);
    }
}
