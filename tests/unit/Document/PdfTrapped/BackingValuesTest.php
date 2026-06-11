<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfTrapped;

use PhpPdf\Document\PdfTrapped;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfTrapped::class)]
final class BackingValuesTest extends TestCase
{
    #[Test]
    public function trappedCaseHasValueTrue(): void
    {
        // Arrange / Act / Assert
        self::assertSame('True', PdfTrapped::Trapped->value);
    }

    #[Test]
    public function notTrappedCaseHasValueFalse(): void
    {
        // Arrange / Act / Assert
        self::assertSame('False', PdfTrapped::NotTrapped->value);
    }

    #[Test]
    public function unknownCaseHasValueUnknown(): void
    {
        // Arrange / Act / Assert
        self::assertSame('Unknown', PdfTrapped::Unknown->value);
    }
}
