<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setMarginBottom')]
final class SetMarginBottomTest extends TestCase
{
    #[Test]
    public function storesMarginBottom(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setMarginBottom(8.0);

        // Assert
        self::assertSame(8.0, $style->getMarginBottom());
    }
}
