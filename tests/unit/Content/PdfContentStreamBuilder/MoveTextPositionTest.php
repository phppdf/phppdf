<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\MoveTextPosition;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'moveTextPosition')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(MoveTextPosition::class)]
final class MoveTextPositionTest extends TestCase
{
    #[Test]
    public function moveTextPositionReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->moveTextPosition(10.0, -14.0));
    }

    #[Test]
    public function moveTextPositionAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->moveTextPosition(10.0, -14.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(MoveTextPosition::class, $ops[0]);
    }
}
