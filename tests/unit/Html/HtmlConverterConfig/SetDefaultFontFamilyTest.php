<?php

declare(strict_types=1);

namespace PhpPdf\Html\HtmlConverterConfig;

use InvalidArgumentException;
use PhpPdf\Font\Type1FontMetrics;
use PhpPdf\Html\HtmlConverterConfig;
use PhpPdf\Html\HtmlFontFamily;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlConverterConfig::class)]
#[CoversMethod(HtmlConverterConfig::class, 'setDefaultFontFamily')]
#[UsesClass(HtmlFontFamily::class)]
#[UsesClass(Type1FontMetrics::class)]
final class SetDefaultFontFamilyTest extends TestCase
{
    #[Test]
    public function setsDefaultFontFamilyToRegisteredPrimaryName(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $config->registerFontFamily('myfont', HtmlFontFamily::type1('Courier'));

        // Act
        $config->setDefaultFontFamily('myfont');

        // Assert
        self::assertSame('myfont', $config->getDefaultFontFamily());
    }

    #[Test]
    public function throwsWhenFamilyIsNotRegistered(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();
        $this->expectException(InvalidArgumentException::class);

        // Act
        $config->setDefaultFontFamily('unknown-family');
    }

    #[Test]
    public function normalizesNameToLowercase(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $config->setDefaultFontFamily('HELVETICA');

        // Assert
        self::assertSame('helvetica', $config->getDefaultFontFamily());
    }

    #[Test]
    public function returnsSelffForFluentInterface(): void
    {
        // Arrange
        $config = new HtmlConverterConfig();

        // Act
        $result = $config->setDefaultFontFamily('helvetica');

        // Assert
        self::assertSame($config, $result);
    }
}
