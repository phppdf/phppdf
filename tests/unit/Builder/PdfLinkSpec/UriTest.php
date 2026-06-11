<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfLinkSpec;

use PhpPdf\Builder\PdfLinkSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLinkSpec::class)]
#[CoversMethod(PdfLinkSpec::class, 'uri')]
final class UriTest extends TestCase
{
    #[Test]
    public function uriStoresCoordinates(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::uri(10.0, 20.0, 150.0, 30.0, 'https://example.com');

        // Assert
        self::assertSame(10.0, $spec->x);
        self::assertSame(20.0, $spec->y);
        self::assertSame(150.0, $spec->width);
        self::assertSame(30.0, $spec->height);
    }

    #[Test]
    public function uriStoresUriString(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::uri(0.0, 0.0, 100.0, 20.0, 'https://example.com');

        // Assert
        self::assertSame('https://example.com', $spec->uri);
    }

    #[Test]
    public function uriSetsPageIndexToNull(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::uri(0.0, 0.0, 100.0, 20.0, 'https://example.com');

        // Assert
        self::assertNull($spec->pageIndex);
    }
}
