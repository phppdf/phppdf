<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setMarginTop')]
final class SetMarginTopTest extends TestCase
{
    #[Test]
    public function storesMarginTop(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setMarginTop(10.0);

        // Assert
        self::assertSame(10.0, $style->getMarginTop());
    }
}
