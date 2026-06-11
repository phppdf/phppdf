<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\CssParser;

use PhpPdf\Html\Internal\CssParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssParser::class)]
#[CoversMethod(CssParser::class, 'parseLength')]
final class ParseLengthTest extends TestCase
{
    #[Test]
    public function parsesZero(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('0', 12.0, 11.0);

        // Assert
        self::assertSame(0.0, $result);
    }

    #[Test]
    public function parsesPtUnitsAsIs(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('12pt', 11.0, 11.0);

        // Assert
        self::assertSame(12.0, $result);
    }

    #[Test]
    public function parsesPxUnitsWithConversion(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('16px', 11.0, 11.0);

        // Assert
        self::assertEqualsWithDelta(12.0, $result, 0.001);
    }

    #[Test]
    public function parsesEmUnitsRelativeToParentFontSize(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('2em', 10.0, 11.0);

        // Assert
        self::assertSame(20.0, $result);
    }

    #[Test]
    public function parsesRemUnitsRelativeToBaseFontSize(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('2rem', 10.0, 11.0);

        // Assert
        self::assertSame(22.0, $result);
    }

    #[Test]
    public function parsesBareIntegerAsPx(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('16', 11.0, 11.0);

        // Assert
        self::assertEqualsWithDelta(12.0, $result, 0.001);
    }

    #[Test]
    public function returnsNullForPercentage(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('50%', 11.0, 11.0);

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function returnsNullForUnsupportedUnit(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('10vw', 11.0, 11.0);

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function parsesNegativeValue(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('-5pt', 11.0, 11.0);

        // Assert
        self::assertSame(-5.0, $result);
    }

    #[Test]
    public function trimsWhitespace(): void
    {
        // Arrange / Act
        $result = CssParser::parseLength('  12pt  ', 11.0, 11.0);

        // Assert
        self::assertSame(12.0, $result);
    }
}
