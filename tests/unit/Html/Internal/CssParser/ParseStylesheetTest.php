<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal\CssParser;

use PhpPdf\Html\Internal\CssParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(CssParser::class)]
#[CoversMethod(CssParser::class, 'parseStylesheet')]
final class ParseStylesheetTest extends TestCase
{
    #[Test]
    public function parsesElementSelector(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('p { color: red; }');

        // Assert
        self::assertSame(['color' => 'red'], $result['p']);
    }

    #[Test]
    public function parsesClassSelector(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('.highlight { background-color: yellow; }');

        // Assert
        self::assertSame(['background-color' => 'yellow'], $result['.highlight']);
    }

    #[Test]
    public function parsesCommaGroupedSelectors(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('h1, h2 { font-weight: bold; }');

        // Assert
        self::assertArrayHasKey('h1', $result);
        self::assertArrayHasKey('h2', $result);
        self::assertSame(['font-weight' => 'bold'], $result['h1']);
        self::assertSame(['font-weight' => 'bold'], $result['h2']);
    }

    #[Test]
    public function stripsBlockComments(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('/* comment */ p { color: blue; }');

        // Assert
        self::assertArrayHasKey('p', $result);
        self::assertSame('blue', $result['p']['color']);
    }

    #[Test]
    public function lowercasesSelectors(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('P { color: red; }');

        // Assert
        self::assertArrayHasKey('p', $result);
    }

    #[Test]
    public function mergesDeclarationsForDuplicateSelectors(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('p { color: red; } p { font-size: 12pt; }');

        // Assert
        self::assertSame('red', $result['p']['color']);
        self::assertSame('12pt', $result['p']['font-size']);
    }

    #[Test]
    public function skipsRulesWithEmptyDeclarations(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('p {} h1 { color: red; }');

        // Assert
        self::assertArrayNotHasKey('p', $result);
        self::assertArrayHasKey('h1', $result);
    }

    #[Test]
    public function returnsEmptyArrayForEmptyStylesheet(): void
    {
        // Arrange / Act
        $result = CssParser::parseStylesheet('');

        // Assert
        self::assertSame([], $result);
    }

    #[Test]
    public function skipsEmptySelectorsFromTrailingComma(): void
    {
        // Arrange — trailing comma creates an empty token after splitting on ','
        // Act
        $result = CssParser::parseStylesheet('p, { color: red; }');

        // Assert — only 'p' is registered, not an empty-string key
        self::assertArrayHasKey('p', $result);
        self::assertArrayNotHasKey('', $result);
    }
}
