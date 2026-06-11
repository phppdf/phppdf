<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Color\Color;
use PhpPdf\Color\ColorType;
use PhpPdf\Content\Operation\SetStrokingCmykColor;
use PhpPdf\Content\Operation\SetStrokingGray;
use PhpPdf\Content\Operation\SetStrokingRgbColor;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'strokeColor')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(Color::class)]
#[UsesClass(ColorType::class)]
#[UsesClass(SetStrokingCmykColor::class)]
#[UsesClass(SetStrokingGray::class)]
#[UsesClass(SetStrokingRgbColor::class)]
final class StrokeColorTest extends TestCase
{
    #[Test]
    public function strokeColorReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->strokeColor(Color::gray(0.5)));
    }

    #[Test]
    public function strokeColorWithGrayAddsSetStrokingGray(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->strokeColor(Color::gray(0.5));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingGray::class, $ops[0]);
    }

    #[Test]
    public function strokeColorWithRgbAddsSetStrokingRgbColor(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->strokeColor(Color::rgb(1.0, 0.0, 0.0));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingRgbColor::class, $ops[0]);
    }

    #[Test]
    public function strokeColorWithCmykAddsSetStrokingCmykColor(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->strokeColor(Color::cmyk(0.0, 0.5, 0.5, 0.0));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingCmykColor::class, $ops[0]);
    }
}
