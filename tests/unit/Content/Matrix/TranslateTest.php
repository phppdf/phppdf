<?php

declare(strict_types=1);

namespace PhpPdf\Content\Matrix;

use PhpPdf\Content\Matrix;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matrix::class)]
#[CoversMethod(Matrix::class, 'translate')]
final class TranslateTest extends TestCase
{
    #[Test]
    public function translateSetsTranslationComponents(): void
    {
        // Arrange / Act
        $m = Matrix::translate(72.0, 720.0);

        // Assert
        self::assertSame(1.0, $m->getA());
        self::assertSame(0.0, $m->getB());
        self::assertSame(0.0, $m->getC());
        self::assertSame(1.0, $m->getD());
        self::assertSame(72.0, $m->getE());
        self::assertSame(720.0, $m->getF());
    }
}
