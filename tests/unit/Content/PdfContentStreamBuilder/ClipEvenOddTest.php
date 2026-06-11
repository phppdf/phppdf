<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetClippingPathEvenOdd;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'clipEvenOdd')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetClippingPathEvenOdd::class)]
final class ClipEvenOddTest extends TestCase
{
    #[Test]
    public function clipEvenOddReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->clipEvenOdd());
    }

    #[Test]
    public function clipEvenOddAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->clipEvenOdd();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetClippingPathEvenOdd::class, $ops[0]);
    }
}
