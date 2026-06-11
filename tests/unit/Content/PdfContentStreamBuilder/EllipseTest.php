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
#[CoversMethod(PdfContentStreamBuilder::class, 'ellipse')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezier::class)]
#[UsesClass(BeginSubpath::class)]
#[UsesClass(CloseSubpath::class)]
final class EllipseTest extends TestCase
{
    #[Test]
    public function ellipseReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->ellipse(100.0, 100.0, 50.0, 25.0));
    }

    #[Test]
    public function ellipseAdds6Operations(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->ellipse(100.0, 100.0, 50.0, 25.0);
        $ops = $builder->build()->getOperations();

        // moveTo (BeginSubpath) + 4x curveTo (AppendCubicBezier) + closePath (CloseSubpath)
        self::assertCount(6, $ops);
        self::assertInstanceOf(BeginSubpath::class, $ops[0]);
        self::assertInstanceOf(AppendCubicBezier::class, $ops[1]);
        self::assertInstanceOf(AppendCubicBezier::class, $ops[2]);
        self::assertInstanceOf(AppendCubicBezier::class, $ops[3]);
        self::assertInstanceOf(AppendCubicBezier::class, $ops[4]);
        self::assertInstanceOf(CloseSubpath::class, $ops[5]);
    }
}
