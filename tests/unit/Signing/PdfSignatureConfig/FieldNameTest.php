<?php

declare(strict_types=1);

namespace PhpPdf\Signing\PdfSignatureConfig;

use PhpPdf\Signing\PdfSignatureConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfSignatureConfig::class)]
#[CoversMethod(PdfSignatureConfig::class, 'fieldName')]
final class FieldNameTest extends TestCase
{
    #[Test]
    public function fieldNameDefaultsToSignature1(): void
    {
        // Arrange / Act
        $config = new PdfSignatureConfig();

        // Assert
        self::assertSame('Signature1', $config->getFieldName());
    }

    #[Test]
    public function fieldNameStoresValue(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $config->fieldName('MySig');

        // Assert
        self::assertSame('MySig', $config->getFieldName());
    }

    #[Test]
    public function fieldNameReturnsSelf(): void
    {
        // Arrange
        $config = new PdfSignatureConfig();

        // Act
        $result = $config->fieldName('MySig');

        // Assert
        self::assertSame($config, $result);
    }
}
