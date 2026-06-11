<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendCubicBezierReplicateFinal;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'curveToReplicateFinal')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezierReplicateFinal::class)]
final class CurveToReplicateFinalTest extends TestCase
{
    #[Test]
    public function curveToReplicateFinalReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->curveToReplicateFinal(1.0, 2.0, 5.0, 6.0));
    }

    #[Test]
    public function curveToReplicateFinalAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->curveToReplicateFinal(1.0, 2.0, 5.0, 6.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(AppendCubicBezierReplicateFinal::class, $ops[0]);
    }
}
