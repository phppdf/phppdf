<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfLinkSpec;

use PhpPdf\Builder\PdfLinkSpec;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfLinkSpec::class)]
#[CoversMethod(PdfLinkSpec::class, 'page')]
final class PageTest extends TestCase
{
    #[Test]
    public function pageStoresCoordinates(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::page(10.0, 20.0, 150.0, 30.0, 2);

        // Assert
        self::assertSame(10.0, $spec->x);
        self::assertSame(20.0, $spec->y);
        self::assertSame(150.0, $spec->width);
        self::assertSame(30.0, $spec->height);
    }

    #[Test]
    public function pageStoresPageIndex(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::page(0.0, 0.0, 100.0, 20.0, 5);

        // Assert
        self::assertSame(5, $spec->pageIndex);
    }

    #[Test]
    public function pageSetsUriToNull(): void
    {
        // Arrange / Act
        $spec = PdfLinkSpec::page(0.0, 0.0, 100.0, 20.0, 0);

        // Assert
        self::assertNull($spec->uri);
    }
}
