<?php

declare(strict_types=1);

namespace PhpPdf\Content\Operation\SetTextMatrix;

use PhpPdf\Content\Matrix;
use PhpPdf\Content\Operation\SetTextMatrix;
use PhpPdf\Object\PdfVersion;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SetTextMatrix::class)]
#[CoversMethod(SetTextMatrix::class, 'minimumVersion')]
#[UsesClass(Matrix::class)]
final class MinimumVersionTest extends TestCase
{
    #[Test]
    public function minimumVersionReturnsExpectedVersion(): void
    {
        // Arrange / Act
        $version = (new SetTextMatrix(Matrix::identity()))->minimumVersion();

        // Assert
        self::assertSame(PdfVersion::PDF_1_0, $version);
    }
}
