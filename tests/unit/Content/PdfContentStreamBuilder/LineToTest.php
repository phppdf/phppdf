<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendLine;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'lineTo')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendLine::class)]
final class LineToTest extends TestCase
{
    #[Test]
    public function lineToReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->lineTo(30.0, 40.0));
    }

    #[Test]
    public function lineToAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->lineTo(30.0, 40.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(AppendLine::class, $ops[0]);
    }
}
