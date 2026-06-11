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
#[CoversMethod(PdfAValidationResult::class, 'getErrorCount')]
#[CoversMethod(PdfAValidationResult::class, 'getWarningCount')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAIssueLevel::class)]
#[UsesClass(PdfAValidationIssue::class)]
final class GetCountsTest extends TestCase
{
    #[Test]
    public function getErrorCountReturnsNumberOfErrors(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [
            new PdfAValidationIssue(PdfAIssueLevel::Error, 'e1', 'Error 1.'),
            new PdfAValidationIssue(PdfAIssueLevel::Error, 'e2', 'Error 2.'),
            new PdfAValidationIssue(PdfAIssueLevel::Warning, 'w1', 'Warning.'),
        ]);

        self::assertSame(2, $result->getErrorCount());
    }

    #[Test]
    public function getWarningCountReturnsNumberOfWarnings(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [
            new PdfAValidationIssue(PdfAIssueLevel::Warning, 'w1', 'Warning 1.'),
            new PdfAValidationIssue(PdfAIssueLevel::Warning, 'w2', 'Warning 2.'),
            new PdfAValidationIssue(PdfAIssueLevel::Error, 'e1', 'Error.'),
        ]);

        self::assertSame(2, $result->getWarningCount());
    }

    #[Test]
    public function bothCountsAreZeroForNoIssues(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, []);

        self::assertSame(0, $result->getErrorCount());
        self::assertSame(0, $result->getWarningCount());
    }
}
