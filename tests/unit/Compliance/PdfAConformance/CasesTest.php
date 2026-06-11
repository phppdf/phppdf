<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAConformance;

use PhpPdf\Compliance\PdfAConformance;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAConformance::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = PdfAConformance::cases();

        // Assert
        self::assertCount(8, $cases);
        self::assertSame('1b', PdfAConformance::PdfA1b->value);
        self::assertSame('2b', PdfAConformance::PdfA2b->value);
    }

    #[Test]
    public function partReturnsPartNumber(): void
    {
        // Arrange / Act / Assert
        self::assertSame(1, PdfAConformance::PdfA1b->part());
        self::assertSame(2, PdfAConformance::PdfA2a->part());
        self::assertSame(3, PdfAConformance::PdfA3u->part());
    }

    #[Test]
    public function conformanceLevelReturnsUppercaseLetter(): void
    {
        // Arrange / Act / Assert
        self::assertSame('B', PdfAConformance::PdfA1b->conformanceLevel());
        self::assertSame('A', PdfAConformance::PdfA2a->conformanceLevel());
        self::assertSame('U', PdfAConformance::PdfA3u->conformanceLevel());
    }
}
