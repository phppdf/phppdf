<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'setTextMatrix')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(Matrix::class)]
#[UsesClass(SetTextMatrix::class)]
final class SetTextMatrixTest extends TestCase
{
    #[Test]
    public function setTextMatrixReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->setTextMatrix(Matrix::translate(72.0, 720.0)));
    }

    #[Test]
    public function setTextMatrixAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->setTextMatrix(Matrix::translate(72.0, 720.0));
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(SetTextMatrix::class, $ops[0]);
    }
}
