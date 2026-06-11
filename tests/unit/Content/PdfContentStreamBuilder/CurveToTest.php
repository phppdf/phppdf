<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendCubicBezier;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'curveTo')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezier::class)]
final class CurveToTest extends TestCase
{
    #[Test]
    public function curveToReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->curveTo(1.0, 2.0, 3.0, 4.0, 5.0, 6.0));
    }

    #[Test]
    public function curveToAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->curveTo(1.0, 2.0, 3.0, 4.0, 5.0, 6.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(AppendCubicBezier::class, $ops[0]);
    }
}
