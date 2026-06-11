<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlConverterConfig;

use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlConverterConfig::class)]
#[CoversMethod(HtmlConverterConfig::class, 'resolveFontFamilyName')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ResolveFontFamilyNameTest extends TestCase
{
    #[Test]
    public function findsPrimaryName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('helvetica');

        // Assert
        self::assertSame('helvetica', $result);
    }

    #[Test]
    public function findsAliasAndReturnsPrimaryName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('arial');

        // Assert
        self::assertSame('helvetica', $result);
    }

    #[Test]
    public function returnsNullForUnknownFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('unknown-font');

        // Assert
        self::assertNull($result);
    }

    #[Test]
    public function parsesCommaSeparatedCssFontFamilyList(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName("'Times New Roman', Times, serif");

        // Assert
        self::assertSame('times-roman', $result);
    }

    #[Test]
    public function returnsFirstMatchInCssFontFamilyList(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('courier, helvetica');

        // Assert
        self::assertSame('courier', $result);
    }

    #[Test]
    public function isCaseInsensitive(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('HELVETICA');

        // Assert
        self::assertSame('helvetica', $result);
    }

    #[Test]
    public function stripsQuotesFromFontNames(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resolveFontFamilyName('"helvetica"');

        // Assert
        self::assertSame('helvetica', $result);
    }
}
