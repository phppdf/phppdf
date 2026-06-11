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
#[CoversMethod(PdfAValidationResult::class, 'isCompliant')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAIssueLevel::class)]
#[UsesClass(PdfAValidationIssue::class)]
final class IsCompliantTest extends TestCase
{
    #[Test]
    public function isCompliantReturnsTrueWhenNoIssues(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, []);

        self::assertTrue($result->isCompliant());
    }

    #[Test]
    public function isCompliantReturnsTrueWhenOnlyWarnings(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [
            new PdfAValidationIssue(PdfAIssueLevel::Warning, 'some.rule', 'A warning.'),
        ]);

        self::assertTrue($result->isCompliant());
    }

    #[Test]
    public function isCompliantReturnsFalseWhenAnyError(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [
            new PdfAValidationIssue(PdfAIssueLevel::Warning, 'warn.rule', 'A warning.'),
            new PdfAValidationIssue(PdfAIssueLevel::Error, 'err.rule', 'An error.'),
        ]);

        self::assertFalse($result->isCompliant());
    }
}
