<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetCharacterSpacing;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setCharacterSpacing')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetCharacterSpacing::class)]
final class SetCharacterSpacingTest extends TestCase
{
    #[Test]
    public function setCharacterSpacingReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setCharacterSpacing(1.0));
    }

    #[Test]
    public function setCharacterSpacingAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setCharacterSpacing(1.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetCharacterSpacing::class, $ops[0]);
    }
}
