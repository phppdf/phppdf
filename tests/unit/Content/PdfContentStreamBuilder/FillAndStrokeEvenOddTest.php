<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\FillAndStrokeEvenOdd;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'fillAndStrokeEvenOdd')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(FillAndStrokeEvenOdd::class)]
final class FillAndStrokeEvenOddTest extends TestCase
{
    #[Test]
    public function fillAndStrokeEvenOddReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->fillAndStrokeEvenOdd());
    }

    #[Test]
    public function fillAndStrokeEvenOddAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillAndStrokeEvenOdd();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(FillAndStrokeEvenOdd::class, $ops[0]);
    }
}
