<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\SetStrokingColorSpace;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setStrokingColorSpace')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(SetStrokingColorSpace::class)]
final class SetStrokingColorSpaceTest extends TestCase
{
    #[Test]
    public function setStrokingColorSpaceReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setStrokingColorSpace('DeviceRGB'));
    }

    #[Test]
    public function setStrokingColorSpaceAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setStrokingColorSpace('DeviceRGB');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetStrokingColorSpace::class, $ops[0]);
    }
}
