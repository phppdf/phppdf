<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Content\Operation\SetNonStrokingCmykColor;
use PhpPdf\Content\Operation\SetNonStrokingGray;
use PhpPdf\Content\Operation\SetNonStrokingRgbColor;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'fillColor')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(Color::class)]
#[UsesClass(ColorType::class)]
#[UsesClass(SetNonStrokingCmykColor::class)]
#[UsesClass(SetNonStrokingGray::class)]
#[UsesClass(SetNonStrokingRgbColor::class)]
final class FillColorTest extends TestCase
{
    #[Test]
    public function fillColorReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->fillColor(Color::black()));
    }

    #[Test]
    public function fillColorWithGrayAddsSetNonStrokingGray(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillColor(Color::gray(0.0));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingGray::class, $ops[0]);
    }

    #[Test]
    public function fillColorWithRgbAddsSetNonStrokingRgbColor(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillColor(Color::rgb(0.0, 1.0, 0.0));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingRgbColor::class, $ops[0]);
    }

    #[Test]
    public function fillColorWithCmykAddsSetNonStrokingCmykColor(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillColor(Color::cmyk(0.1, 0.2, 0.3, 0.4));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingCmykColor::class, $ops[0]);
    }
}
