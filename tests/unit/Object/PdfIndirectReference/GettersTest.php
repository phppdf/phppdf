<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfIndirectReference;

use PhpPdf\Object\PdfIndirectReference;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfIndirectReference::class)]
#[CoversMethod(PdfIndirectReference::class, 'getObjectNumber')]
#[CoversMethod(PdfIndirectReference::class, 'getGenerationNumber')]
final class GettersTest extends TestCase
{
    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        // Arrange
        $ref = new PdfIndirectReference(3, 0);

        // Act / Assert
        self::assertSame(3, $ref->getObjectNumber());
        self::assertSame(0, $ref->getGenerationNumber());
    }
}
