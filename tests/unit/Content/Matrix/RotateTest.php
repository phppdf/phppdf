<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'rotate')]
final class RotateTest extends TestCase
{
    #[Test]
    public function rotateZeroDegreesIsIdentity(): void
    {
        // Arrange / Act
        $m = Matrix::rotate(0.0);

        // Assert
        self::assertEqualsWithDelta(1.0, $m->getA(), 0.0001);
        self::assertEqualsWithDelta(0.0, $m->getB(), 0.0001);
        self::assertEqualsWithDelta(0.0, $m->getC(), 0.0001);
        self::assertEqualsWithDelta(1.0, $m->getD(), 0.0001);
    }

    #[Test]
    public function rotate90DegreesGivesCorrectComponents(): void
    {
        // Arrange / Act
        $m = Matrix::rotate(90.0);

        // Assert
        self::assertEqualsWithDelta(0.0, $m->getA(), 0.0001);
        self::assertEqualsWithDelta(1.0, $m->getB(), 0.0001);
        self::assertEqualsWithDelta(-1.0, $m->getC(), 0.0001);
        self::assertEqualsWithDelta(0.0, $m->getD(), 0.0001);
    }
}
