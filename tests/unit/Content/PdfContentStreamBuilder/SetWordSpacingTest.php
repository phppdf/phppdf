<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetWordSpacing;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setWordSpacing')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetWordSpacing::class)]
final class SetWordSpacingTest extends TestCase
{
    #[Test]
    public function setWordSpacingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setWordSpacing(2.0));
    }

    #[Test]
    public function setWordSpacingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setWordSpacing(2.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetWordSpacing::class, $ops[0]);
    }
}
