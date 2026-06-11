<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\PaintShading;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'paintShading')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(PaintShading::class)]
final class PaintShadingTest extends TestCase
{
    #[Test]
    public function paintShadingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->paintShading('Sh1'));
    }

    #[Test]
    public function paintShadingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->paintShading('Sh1');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(PaintShading::class, $ops[0]);
    }
}
