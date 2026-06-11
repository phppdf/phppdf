<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfAnnotation;

use PhpPdf\Reader\PdfAnnotation;
use PhpPdf\Reader\PdfAnnotationType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAnnotation::class)]
#[CoversMethod(PdfAnnotation::class, 'isUriLink')]
final class IsUriLinkTest extends TestCase
{
    #[Test]
    public function returnsTrueForLinkWithUri(): void
    {
        // Arrange
        $annotation = new PdfAnnotation(
            type: PdfAnnotationType::Link,
            x: 0.0,
            y: 0.0,
            width: 100.0,
            height: 20.0,
            uri: 'https://example.com',
        );

        // Act / Assert
        self::assertTrue($annotation->isUriLink());
    }

    #[Test]
    public function returnsFalseForLinkWithoutUri(): void
    {
        // Arrange
        $annotation = new PdfAnnotation(
            type: PdfAnnotationType::Link,
            x: 0.0,
            y: 0.0,
            width: 100.0,
            height: 20.0,
            uri: null,
        );

        // Act / Assert
        self::assertFalse($annotation->isUriLink());
    }

    #[Test]
    public function returnsFalseForNonLinkType(): void
    {
        // Arrange
        $annotation = new PdfAnnotation(
            type: PdfAnnotationType::Text,
            x: 0.0,
            y: 0.0,
            width: 100.0,
            height: 20.0,
            uri: 'https://example.com',
        );

        // Act / Assert
        self::assertFalse($annotation->isUriLink());
    }
}
