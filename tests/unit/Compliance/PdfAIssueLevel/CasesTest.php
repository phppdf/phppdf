<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAIssueLevel;

use PhpPdf\Compliance\PdfAIssueLevel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAIssueLevel::class)]
final class CasesTest extends TestCase
{
    #[Test]
    public function enumHasExpectedCases(): void
    {
        // Arrange / Act
        $cases = PdfAIssueLevel::cases();

        // Assert
        self::assertCount(2, $cases);
        self::assertSame('error', PdfAIssueLevel::Error->value);
        self::assertSame('warning', PdfAIssueLevel::Warning->value);
    }
}
