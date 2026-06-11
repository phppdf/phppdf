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
#[CoversMethod(HtmlConverterConfig::class, 'resourceName')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class ResourceNameTest extends TestCase
{
    #[Test]
    public function generatesNormalVariantName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resourceName('helvetica', false, false);

        // Assert
        self::assertSame('F0N', $result);
    }

    #[Test]
    public function generatesBoldVariantName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resourceName('helvetica', true, false);

        // Assert
        self::assertSame('F0B', $result);
    }

    #[Test]
    public function generatesItalicVariantName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resourceName('helvetica', false, true);

        // Assert
        self::assertSame('F0I', $result);
    }

    #[Test]
    public function generatesBoldItalicVariantName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->resourceName('helvetica', true, true);

        // Assert
        self::assertSame('F0X', $result);
    }

    #[Test]
    public function usesFamilyIndexInResourceName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $timesResult = $config->resourceName('times-roman', false, false);
        $courierResult = $config->resourceName('courier', false, false);

        // Assert
        self::assertSame('F1N', $timesResult);
        self::assertSame('F2N', $courierResult);
    }
}
