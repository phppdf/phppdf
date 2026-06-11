<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\CloseFillAndStrokeEvenOdd;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'closeFillAndStrokeEvenOdd')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(CloseFillAndStrokeEvenOdd::class)]
final class CloseFillAndStrokeEvenOddTest extends TestCase
{
    #[Test]
    public function closeFillAndStrokeEvenOddReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->closeFillAndStrokeEvenOdd());
    }

    #[Test]
    public function closeFillAndStrokeEvenOddAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->closeFillAndStrokeEvenOdd();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(CloseFillAndStrokeEvenOdd::class, $ops[0]);
    }
}
