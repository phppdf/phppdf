<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\CloseFillAndStroke;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'closeFillAndStroke')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(CloseFillAndStroke::class)]
final class CloseFillAndStrokeTest extends TestCase
{
    #[Test]
    public function closeFillAndStrokeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->closeFillAndStroke());
    }

    #[Test]
    public function closeFillAndStrokeAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->closeFillAndStroke();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(CloseFillAndStroke::class, $ops[0]);
    }
}
