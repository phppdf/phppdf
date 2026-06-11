<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\StrokePath;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'stroke')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(StrokePath::class)]
final class StrokeTest extends TestCase
{
    #[Test]
    public function strokeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->stroke());
    }

    #[Test]
    public function strokeAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->stroke();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(StrokePath::class, $ops[0]);
    }
}
