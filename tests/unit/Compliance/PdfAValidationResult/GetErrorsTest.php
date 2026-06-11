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
#[CoversMethod(PdfAValidationResult::class, 'getErrors')]
#[UsesClass(PdfAConformance::class)]
#[UsesClass(PdfAIssueLevel::class)]
#[UsesClass(PdfAValidationIssue::class)]
final class GetErrorsTest extends TestCase
{
    #[Test]
    public function getErrorsReturnsEmptyWhenNoIssues(): void
    {
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, []);

        self::assertSame([], $result->getErrors());
    }

    #[Test]
    public function getErrorsReturnsOnlyErrors(): void
    {
        $error = new PdfAValidationIssue(PdfAIssueLevel::Error, 'err', 'Error.');
        $warning = new PdfAValidationIssue(PdfAIssueLevel::Warning, 'warn', 'Warning.');
        $result = new PdfAValidationResult(PdfAConformance::PdfA2b, [$error, $warning]);

        $errors = $result->getErrors();

        self::assertCount(1, $errors);
        self::assertSame($error, $errors[0]);
    }
}
