<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAValidationResult;

use PhpPdf\Compliance\PdfAConformance;
use PhpPdf\Compliance\PdfAIssueLevel;
use PhpPdf\Compliance\PdfAValidationIssue;
use PhpPdf\Compliance\PdfAValidationResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAValidationResult::class)]
#[CoversMethod(PdfAValidationResult::class, 'getWarnings')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAIssueLevel::class)]
#[UsesClass(PdfAValidationIssue::class)]
final class GetWarningsTest extends TestCase
{
    #[Test]
    public function getWarningsReturnsEmptyWhenNoIssues(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, []);

        self::assertSame([], $result->getWarnings());
    }

    #[Test]
    public function getWarningsReturnsOnlyWarnings(): void
    {
        $error = new PdfAValidationIssue(PdfAIssueLevel::Error, 'err', 'Error.');
        $warning = new PdfAValidationIssue(PdfAIssueLevel::Warning, 'warn', 'Warning.');
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [$error, $warning]);

        $warnings = $result->getWarnings();

        self::assertCount(1, $warnings);
        self::assertSame($warning, $warnings[0]);
    }
}
