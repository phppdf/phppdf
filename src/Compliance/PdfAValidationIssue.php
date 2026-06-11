<?php

declare(strict_types=1);

namespace PhpPdf\Compliance;

/**
 * A single rule violation or advisory notice produced by PdfAValidator.
 *
 * Rules are identified by a short kebab-case code (e.g. 'font.not-embedded',
 * 'trailer.id') so callers can filter or group by rule type without parsing
 * the human-readable message.
 */
final class PdfAValidationIssue
{
    public function __construct(
        public readonly PdfAIssueLevel $level,
        public readonly string $rule,
        public readonly string $message,
    ) {
    }

    public function isError(): bool
    {
        return $this->level === PdfAIssueLevel::Error;
    }

    public function isWarning(): bool
    {
        return $this->level === PdfAIssueLevel::Warning;
    }
}
