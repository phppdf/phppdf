<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetFlatnessTolerance;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setFlatnessTolerance')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetFlatnessTolerance::class)]
final class SetFlatnessToleranceTest extends TestCase
{
    #[Test]
    public function setFlatnessToleranceReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setFlatnessTolerance(1.0));
    }

    #[Test]
    public function setFlatnessToleranceAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setFlatnessTolerance(1.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetFlatnessTolerance::class, $ops[0]);
    }
}
