<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'scale')]
final class ScaleTest extends TestCase
{
    #[Test]
    public function scaleUniformUsesScaleForBothAxes(): void
    {
        // Arrange / Act
        $m = Matrix::scale(2.0);

        // Assert
        self::assertSame(2.0, $m->getA());
        self::assertSame(2.0, $m->getD());
        self::assertSame(0.0, $m->getE());
    }

    #[Test]
    public function scaleNonUniformSetsBothAxes(): void
    {
        // Arrange / Act
        $m = Matrix::scale(2.0, 0.5);

        // Assert
        self::assertSame(2.0, $m->getA());
        self::assertSame(0.5, $m->getD());
    }
}
