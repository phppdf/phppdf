<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\MoveTextPositionAndSetLeading;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'moveTextPositionAndSetLeading')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(MoveTextPositionAndSetLeading::class)]
final class MoveTextPositionAndSetLeadingTest extends TestCase
{
    #[Test]
    public function moveTextPositionAndSetLeadingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->moveTextPositionAndSetLeading(0.0, -14.0));
    }

    #[Test]
    public function moveTextPositionAndSetLeadingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->moveTextPositionAndSetLeading(0.0, -14.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(MoveTextPositionAndSetLeading::class, $ops[0]);
    }
}
