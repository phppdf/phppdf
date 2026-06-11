<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetSpacingMoveToNextLineAndShowText;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setSpacingMoveToNextLineAndShowText')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetSpacingMoveToNextLineAndShowText::class)]
final class SetSpacingMoveToNextLineAndShowTextTest extends TestCase
{
    #[Test]
    public function setSpacingMoveToNextLineAndShowTextReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame(
            $builder,
            $builder->setSpacingMoveToNextLineAndShowText(2.0, 0.5, 'Hello'),
        );
    }

    #[Test]
    public function setSpacingMoveToNextLineAndShowTextAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setSpacingMoveToNextLineAndShowText(2.0, 0.5, 'Hello');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetSpacingMoveToNextLineAndShowText::class, $ops[0]);
    }
}
