<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\InvokeXObject;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'invokeXObject')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(InvokeXObject::class)]
final class InvokeXObjectTest extends TestCase
{
    #[Test]
    public function invokeXObjectReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->invokeXObject('Im1'));
    }

    #[Test]
    public function invokeXObjectAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->invokeXObject('Im1');
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(InvokeXObject::class, $ops[0]);
    }
}
