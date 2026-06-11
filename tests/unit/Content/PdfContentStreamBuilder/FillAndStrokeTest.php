<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\FillAndStroke;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'fillAndStroke')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(FillAndStroke::class)]
final class FillAndStrokeTest extends TestCase
{
    #[Test]
    public function fillAndStrokeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->fillAndStroke());
    }

    #[Test]
    public function fillAndStrokeAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->fillAndStroke();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(FillAndStroke::class, $ops[0]);
    }
}
