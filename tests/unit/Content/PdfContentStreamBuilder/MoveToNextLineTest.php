<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\MoveToNextLine;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'moveToNextLine')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(MoveToNextLine::class)]
final class MoveToNextLineTest extends TestCase
{
    #[Test]
    public function moveToNextLineReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->moveToNextLine());
    }

    #[Test]
    public function moveToNextLineAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->moveToNextLine();
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(MoveToNextLine::class, $ops[0]);
    }
}
