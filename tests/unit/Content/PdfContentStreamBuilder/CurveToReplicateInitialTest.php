<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendCubicBezierReplicateInitial;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'curveToReplicateInitial')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendCubicBezierReplicateInitial::class)]
final class CurveToReplicateInitialTest extends TestCase
{
    #[Test]
    public function curveToReplicateInitialReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->curveToReplicateInitial(3.0, 4.0, 5.0, 6.0));
    }

    #[Test]
    public function curveToReplicateInitialAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->curveToReplicateInitial(3.0, 4.0, 5.0, 6.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(AppendCubicBezierReplicateInitial::class, $ops[0]);
    }
}
