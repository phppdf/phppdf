<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetHorizontalTextScaling;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setHorizontalTextScaling')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetHorizontalTextScaling::class)]
final class SetHorizontalTextScalingTest extends TestCase
{
    #[Test]
    public function setHorizontalTextScalingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setHorizontalTextScaling(100.0));
    }

    #[Test]
    public function setHorizontalTextScalingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setHorizontalTextScaling(100.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetHorizontalTextScaling::class, $ops[0]);
    }
}
