<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setBackgroundColor')]
final class SetBackgroundColorTest extends TestCase
{
    #[Test]
    public function storesBackgroundColor(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setBackgroundColor([0.9, 0.9, 0.9]);

        // Assert
        self::assertSame([0.9, 0.9, 0.9], $style->getBackgroundColor());
    }

    #[Test]
    public function storesNull(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $style->setBackgroundColor([0.5, 0.5, 0.5]);

        // Act
        $style->setBackgroundColor(null);

        // Assert
        self::assertNull($style->getBackgroundColor());
    }
}
