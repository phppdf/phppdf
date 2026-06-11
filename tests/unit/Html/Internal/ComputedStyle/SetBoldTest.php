<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'setBold')]
final class SetBoldTest extends TestCase
{
    #[Test]
    public function storesBold(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);

        // Act
        $style->setBold(true);

        // Assert
        self::assertTrue($style->isBold());
    }
}
