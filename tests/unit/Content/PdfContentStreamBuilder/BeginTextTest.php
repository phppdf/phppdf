<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\BeginText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'beginText')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(BeginText::class)]
final class BeginTextTest extends TestCase
{
    #[Test]
    public function beginTextReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->beginText());
    }

    #[Test]
    public function beginTextAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->beginText();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(BeginText::class, $ops[0]);
    }
}
