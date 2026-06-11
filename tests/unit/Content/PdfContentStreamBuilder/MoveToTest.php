<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\BeginSubpath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'moveTo')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginSubpath::class)]
final class MoveToTest extends TestCase
{
    #[Test]
    public function moveToReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->moveTo(10.0, 20.0));
    }

    #[Test]
    public function moveToAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->moveTo(10.0, 20.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(BeginSubpath::class, $ops[0]);
    }
}
