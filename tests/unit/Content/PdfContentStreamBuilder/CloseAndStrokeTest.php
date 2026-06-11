<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\CloseAndStroke;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'closeAndStroke')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(CloseAndStroke::class)]
final class CloseAndStrokeTest extends TestCase
{
    #[Test]
    public function closeAndStrokeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->closeAndStroke());
    }

    #[Test]
    public function closeAndStrokeAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->closeAndStroke();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(CloseAndStroke::class, $ops[0]);
    }
}
