<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\ConcatenateMatrix;
use PhpPdf\Content\Operation\InvokeXObject;
use PhpPdf\Content\Operation\RestoreGraphicsState;
use PhpPdf\Content\Operation\SaveGraphicsState;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'drawImportedPage')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(ConcatenateMatrix::class)]
#[UsesClass(InvokeXObject::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(RestoreGraphicsState::class)]
#[UsesClass(SaveGraphicsState::class)]
final class DrawImportedPageTest extends TestCase
{
    #[Test]
    public function drawImportedPageReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->drawImportedPage('Page1', 0.0, 0.0, 1.0));
    }

    #[Test]
    public function drawImportedPageAdds4Operations(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->drawImportedPage('Page1', 10.0, 20.0, 2.0);
        $ops = $builder->build()->getOperations();

        self::assertCount(4, $ops);
        self::assertInstanceOf(SaveGraphicsState::class, $ops[0]);
        self::assertInstanceOf(ConcatenateMatrix::class, $ops[1]);
        self::assertInstanceOf(InvokeXObject::class, $ops[2]);
        self::assertInstanceOf(RestoreGraphicsState::class, $ops[3]);
    }
}
