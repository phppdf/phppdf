<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\ComputedStyle;

use PhpPdf\Html\Internal\ComputedStyle;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ComputedStyle::class)]
#[CoversMethod(ComputedStyle::class, 'effectiveLineHeight')]
final class EffectiveLineHeightTest extends TestCase
{
    #[Test]
    public function usesExplicitLineHeightWhenSet(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 11.0);
        $style->setLineHeight(20.0);

        // Act
        $result = $style->effectiveLineHeight();

        // Assert
        self::assertSame(20.0, $result);
    }

    #[Test]
    public function fallsBackToFontSizeTimesDefaultMultiplier(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 10.0);

        // Act
        $result = $style->effectiveLineHeight();

        // Assert
        self::assertSame(14.0, $result);
    }

    #[Test]
    public function usesCustomMultiplierWhenLineHeightIsZero(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 10.0);

        // Act
        $result = $style->effectiveLineHeight(2.0);

        // Assert
        self::assertSame(20.0, $result);
    }

    #[Test]
    public function usesExplicitLineHeightOverCustomMultiplier(): void
    {
        // Arrange
        $style = new ComputedStyle('helvetica', 10.0);
        $style->setLineHeight(15.0);

        // Act
        $result = $style->effectiveLineHeight(2.0);

        // Assert
        self::assertSame(15.0, $result);
    }
}
