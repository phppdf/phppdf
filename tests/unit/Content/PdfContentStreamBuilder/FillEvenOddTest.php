<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\FillPathEvenOdd;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'fillEvenOdd')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(FillPathEvenOdd::class)]
final class FillEvenOddTest extends TestCase
{
    #[Test]
    public function fillEvenOddReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->fillEvenOdd());
    }

    #[Test]
    public function fillEvenOddAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillEvenOdd();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(FillPathEvenOdd::class, $ops[0]);
    }
}
