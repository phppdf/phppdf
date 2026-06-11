<?php

declare(strict_types=1);

namespace PhpPdf\Font\PdfFontCompiler;

use PhpPdf\Font\PdfFontCompiler;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfIndirectObject;
use PhpPdf\Object\PdfIndirectReference;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfObjectRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(PdfFontCompiler::class)]
#[CoversMethod(PdfFontCompiler::class, 'compileType1')]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfIndirectObject::class)]
#[UsesClass(PdfIndirectReference::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfObjectRegistry::class)]
final class CompileType1Test extends TestCase
{
    #[Test]
    public function compileType1ReturnsPdfIndirectReference(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act
        $ref = PdfFontCompiler::compileType1($registry, 'Helvetica');

        // Assert
        self::assertInstanceOf(PdfIndirectReference::class, $ref);
    }

    #[Test]
    public function compileType1RegistersObjectInRegistry(): void
    {
        // Arrange
        $registry = new PdfObjectRegistry();

        // Act
        PdfFontCompiler::compileType1($registry, 'Times-Roman');

        // Assert
        self::assertCount(1, $registry->all());
    }

    #[Test]
    public function constructorIsPrivateAndCanBeInvoked(): void
    {
        // Arrange
        $reflection = new ReflectionClass(PdfFontCompiler::class);
        $constructor = $reflection->getConstructor();
        self::assertNotNull($constructor);

        // Act
        $instance = $reflection->newInstanceWithoutConstructor();
        $result = $constructor->invoke($instance);

        // Assert
        self::assertTrue($constructor->isPrivate());
        self::assertNull($result);
    }
}
