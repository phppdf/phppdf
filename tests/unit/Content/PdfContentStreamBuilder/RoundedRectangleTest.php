<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendCubicBezier;
use PhpPdf\Content\Operation\AppendLine;
use PhpPdf\Content\Operation\BeginSubpath;
use PhpPdf\Content\Operation\CloseSubpath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'roundedRectangle')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezier::class)]
#[UsesClass(AppendLine::class)]
#[UsesClass(BeginSubpath::class)]
#[UsesClass(CloseSubpath::class)]
final class RoundedRectangleTest extends TestCase
{
    #[Test]
    public function roundedRectangleReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->roundedRectangle(0.0, 0.0, 100.0, 50.0, 5.0));
    }

    #[Test]
    public function roundedRectangleAddsCorrectOperations(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->roundedRectangle(0.0, 0.0, 100.0, 50.0, 5.0);
        $ops = $builder->build()->getOperations();

        // moveTo + 4x lineTo + 4x curveTo + closePath = 10 operations
        self::assertCount(10, $ops);
        self::assertInstanceOf(BeginSubpath::class, $ops[0]);
        self::assertInstanceOf(CloseSubpath::class, $ops[9]);
    }

    #[Test]
    public function roundedRectangleClampsCornersToHalfWidth(): void
    {
        $builder = new PdfContentStreamBuilder();
        // radius larger than half the width — should be clamped
        $builder->roundedRectangle(0.0, 0.0, 10.0, 20.0, 100.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(10, $ops);
    }
}
