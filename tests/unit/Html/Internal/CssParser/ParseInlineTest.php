<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\CssParser;

use PhpPdf\Html\Internal\CssParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssParser::class)]
#[CoversMethod(CssParser::class, 'parseInline')]
final class ParseInlineTest extends TestCase
{
    #[Test]
    public function parsesPropertyAndValue(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('color: red');

        // Assert
        self::assertSame(['color' => 'red'], $result);
    }

    #[Test]
    public function parsesMultipleDeclarations(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('color: red; font-size: 14px');

        // Assert
        self::assertSame(['color' => 'red', 'font-size' => '14px'], $result);
    }

    #[Test]
    public function lowercasesPropertyNames(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('Font-Size: 12pt');

        // Assert
        self::assertArrayHasKey('font-size', $result);
    }

    #[Test]
    public function trimsWhitespace(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('  color  :  blue  ');

        // Assert
        self::assertSame(['color' => 'blue'], $result);
    }

    #[Test]
    public function skipsEmptyDeclarations(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('color: red;;font-size: 12pt;');

        // Assert
        self::assertCount(2, $result);
    }

    #[Test]
    public function skipsDeclarationsWithoutColon(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('invalid; color: red');

        // Assert
        self::assertSame(['color' => 'red'], $result);
    }

    #[Test]
    public function returnsEmptyArrayForEmptyString(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('');

        // Assert
        self::assertSame([], $result);
    }

    #[Test]
    public function preservesValueCase(): void
    {
        // Arrange / Act
        $result = CssParser::parseInline('font-family: Arial, Sans-Serif');

        // Assert
        self::assertSame('Arial, Sans-Serif', $result['font-family']);
    }
}
