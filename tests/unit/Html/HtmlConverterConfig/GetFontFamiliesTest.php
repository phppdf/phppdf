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
#[CoversMethod(HtmlConverterConfig::class, 'getFontFamilies')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class GetFontFamiliesTest extends TestCase
{
    #[Test]
    public function returnsAllRegisteredFamiliesInOrder(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $families = $config->getFontFamilies();

        // Assert
        self::assertArrayHasKey('helvetica', $families);
        self::assertArrayHasKey('times-roman', $families);
        self::assertArrayHasKey('courier', $families);
    }

    #[Test]
    public function returnsHtmlFontFamilyInstances(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $families = $config->getFontFamilies();

        // Assert
        foreach ($families as $family) {
            self::assertInstanceOf(HtmlFontFamily::class, $family);
        }
    }

    #[Test]
    public function includesCustomRegisteredFamily(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->registerFontFamily('custom', HtmlFontFamily::type1('Courier'));

        // Act
        $families = $config->getFontFamilies();

        // Assert
        self::assertArrayHasKey('custom', $families);
    }
}
