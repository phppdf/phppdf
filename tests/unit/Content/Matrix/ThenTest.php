<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'then')]
final class ThenTest extends TestCase
{
    #[Test]
    public function thenIdentityIsNoOp(): void
    {
        // Arrange
        $m = Matrix::translate(10.0, 20.0);

        // Act
        $result = $m->then(Matrix::identity());

        // Assert
        self::assertEqualsWithDelta(10.0, $result->getE(), 0.0001);
        self::assertEqualsWithDelta(20.0, $result->getF(), 0.0001);
    }

    #[Test]
    public function thenChainsTranslations(): void
    {
        // Arrange
        $a = Matrix::translate(10.0, 0.0);
        $b = Matrix::translate(5.0, 3.0);

        // Act
        $result = $a->then($b);

        // Assert
        self::assertEqualsWithDelta(15.0, $result->getE(), 0.0001);
        self::assertEqualsWithDelta(3.0, $result->getF(), 0.0001);
    }
}
