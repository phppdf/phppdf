<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Operation\AppendRectangle;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'rectangle')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(AppendRectangle::class)]
final class RectangleTest extends TestCase
{
    #[Test]
    public function rectangleReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->rectangle(0.0, 0.0, 100.0, 50.0));
    }

    #[Test]
    public function rectangleAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->rectangle(0.0, 0.0, 100.0, 50.0);
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(AppendRectangle::class, $ops[0]);
    }
}
