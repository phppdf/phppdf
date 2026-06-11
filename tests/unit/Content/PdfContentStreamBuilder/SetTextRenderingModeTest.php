<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetTextRenderingMode;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setTextRenderingMode')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetTextRenderingMode::class)]
final class SetTextRenderingModeTest extends TestCase
{
    #[Test]
    public function setTextRenderingModeReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setTextRenderingMode(0));
    }

    #[Test]
    public function setTextRenderingModeAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setTextRenderingMode(0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetTextRenderingMode::class, $ops[0]);
    }
}
