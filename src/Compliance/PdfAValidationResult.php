<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

/**
 * The result of a PdfAValidator run.
 *
 * A document is considered compliant when there are zero errors. Warnings
 * indicate advisory issues that do not formally violate the standard but may
 * affect interoperability (e.g. missing /OutputIntents when device colorspaces
 * are used).
 */
final class PdfAValidationResult
{
    /** @param list<\PhpPdf\Compliance\PdfAValidationIssue> $issues */
    public function __construct(public readonly PdfAConformance $conformance, public readonly array $issues,)
    {
    }

    /**
     * Returns true when no errors were found (warnings are allowed).
     */
    public function isCompliant(): bool
    {
        foreach ($this->issues as $issue) {
            if ($issue->isError()) {
                return false;
            }
        }

        return true;
    }

    /** @return list<\PhpPdf\Compliance\PdfAValidationIssue> */
    public function getErrors(): array
    {
        return array_values(array_filter($this->issues, static fn ($i) => $i->isError()));
    }

    /** @return list<\PhpPdf\Compliance\PdfAValidationIssue> */
    public function getWarnings(): array
    {
        return array_values(array_filter($this->issues, static fn ($i) => $i->isWarning()));
    }

    public function getErrorCount(): int
    {
        return count($this->getErrors());
    }

    public function getWarningCount(): int
    {
        return count($this->getWarnings());
    }
}
