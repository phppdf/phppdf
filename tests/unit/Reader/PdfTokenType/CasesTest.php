<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfTokenType;

use PhpPdf\Reader\PdfTokenType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTokenType::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = PdfTokenType::cases();

        // Assert
        self::assertCount(10, $cases);
        self::assertContains(PdfTokenType::Integer, $cases);
        self::assertContains(PdfTokenType::Eof, $cases);
    }
}
