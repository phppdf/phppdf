<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendCubicBezier;
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
#[CoversMethod(PdfContentStreamBuilder::class, 'circle')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezier::class)]
#[UsesClass(BeginSubpath::class)]
#[UsesClass(CloseSubpath::class)]
final class CircleTest extends TestCase
{
    #[Test]
    public function circleReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->circle(50.0, 50.0, 25.0));
    }

    #[Test]
    public function circleDelegatesToEllipseWithEqualRadii(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->circle(50.0, 50.0, 25.0);
        $ops = $builder->build()->getOperations();

        // circle(cx, cy, r) → ellipse(cx, cy, r, r) → 6 operations
        self::assertCount(6, $ops);
        self::assertInstanceOf(BeginSubpath::class, $ops[0]);
        self::assertInstanceOf(CloseSubpath::class, $ops[5]);
    }
}
