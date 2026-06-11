<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'toPdfString')]
final class ToPdfStringTest extends TestCase
{
    #[Test]
    public function toPdfStringFormatsIdentityMatrix(): void
    {
        // Arrange / Act
        $s = Matrix::identity()->toPdfString();

        // Assert
        self::assertSame('1 0 0 1 0 0', $s);
    }

    #[Test]
    public function toPdfStringStripsTrailingZeros(): void
    {
        // Arrange / Act
        $s = Matrix::translate(72.5, 720.0)->toPdfString();

        // Assert
        self::assertSame('1 0 0 1 72.5 720', $s);
    }
}
