<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\MoveToNextLineAndShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'moveToNextLineAndShowText')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(MoveToNextLineAndShowText::class)]
final class MoveToNextLineAndShowTextTest extends TestCase
{
    #[Test]
    public function moveToNextLineAndShowTextReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->moveToNextLineAndShowText('Hello'));
    }

    #[Test]
    public function moveToNextLineAndShowTextAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->moveToNextLineAndShowText('Hello');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(MoveToNextLineAndShowText::class, $ops[0]);
    }
}
