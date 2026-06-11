<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetNonStrokingColorSpace;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setNonStrokingColorSpace')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetNonStrokingColorSpace::class)]
final class SetNonStrokingColorSpaceTest extends TestCase
{
    #[Test]
    public function setNonStrokingColorSpaceReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setNonStrokingColorSpace('DeviceCMYK'));
    }

    #[Test]
    public function setNonStrokingColorSpaceAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setNonStrokingColorSpace('DeviceCMYK');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetNonStrokingColorSpace::class, $ops[0]);
    }
}
