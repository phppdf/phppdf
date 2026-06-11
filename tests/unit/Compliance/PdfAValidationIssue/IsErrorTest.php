<?php

declare(strict_types=1);

namespace PhpPdf\Compliance\PdfAValidationIssue;

use PhpPdf\Compliance\PdfAIssueLevel;
use PhpPdf\Compliance\PdfAValidationIssue;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfAValidationIssue::class)]
#[CoversMethod(PdfAValidationIssue::class, 'isError')]
#[UsesClass(PdfAIssueLevel::class)]
final class IsErrorTest extends TestCase
{
    #[Test]
    public function isErrorReturnsTrueForErrorLevel(): void
    {
        $issue = new PdfAValidationIssue(PdfAIssueLevel::Error, 'test.rule', 'Error message.');

        self::assertTrue($issue->isError());
    }

    #[Test]
    public function isErrorReturnsFalseForWarningLevel(): void
    {
        $issue = new PdfAValidationIssue(PdfAIssueLevel::Warning, 'test.rule', 'Warning message.');

        self::assertFalse($issue->isError());
    }
}
