<?php

declare(strict_types=1);

namespace PhpPdf\Content\PdfContentStreamBuilder;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\ConcatenateMatrix;
use PhpPdf\Content\PdfContentStreamBuilder;
use PhpPdf\Object\PdfContentStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfContentStreamBuilder::class)]
#[CoversMethod(PdfContentStreamBuilder::class, 'concatenateMatrix')]
#[UsesClass(PdfContentStream::class)]
#[UsesClass(ConcatenateMatrix::class)]
#[UsesClass(Matrix::class)]
final class ConcatenateMatrixTest extends TestCase
{
    #[Test]
    public function concatenateMatrixReturnsSelf(): void
    {
        $builder = new PdfContentStreamBuilder();
        self::assertSame($builder, $builder->concatenateMatrix(Matrix::identity()));
    }

    #[Test]
    public function concatenateMatrixAddsOperation(): void
    {
        $builder = new PdfContentStreamBuilder();
        $builder->concatenateMatrix(Matrix::identity());
        $ops = $builder->build()->getOperations();
        self::assertCount(1, $ops);
        self::assertInstanceOf(ConcatenateMatrix::class, $ops[0]);
    }
}
