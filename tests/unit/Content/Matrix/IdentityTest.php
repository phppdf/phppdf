<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'identity')]
final class IdentityTest extends TestCase
{
    #[Test]
    public function identityReturnsUnitMatrix(): void
    {
        // Arrange / Act
        $m = Matrix::identity();

        // Assert
        self::assertSame(1.0, $m->getA());
        self::assertSame(0.0, $m->getB());
        self::assertSame(0.0, $m->getC());
        self::assertSame(1.0, $m->getD());
        self::assertSame(0.0, $m->getE());
        self::assertSame(0.0, $m->getF());
    }
}
