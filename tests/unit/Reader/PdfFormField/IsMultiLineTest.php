<?php

declare(strict_types=1);

namespace PhpPdf\Reader\PdfFormField;

use PhpPdf\Reader\PdfFormField;
use PhpPdf\Reader\PdfFormFieldType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormField::class)]
#[CoversMethod(PdfFormField::class, 'isMultiLine')]
final class IsMultiLineTest extends TestCase
{
    #[Test]
    public function returnsTrueWhenMultiLine(): void
    {
        // Arrange
        $field = new PdfFormField(
            objectNumber: 1,
            generationNumber: 0,
            name: 'notes',
            fullName: 'notes',
            type: PdfFormFieldType::Text,
            value: null,
            multiLine: true,
        );

        // Act / Assert
        self::assertTrue($field->isMultiLine());
    }

    #[Test]
    public function returnsFalseWhenSingleLine(): void
    {
        // Arrange
        $field = new PdfFormField(
            objectNumber: 1,
            generationNumber: 0,
            name: 'name',
            fullName: 'name',
            type: PdfFormFieldType::Text,
            value: null,
            multiLine: false,
        );

        // Act / Assert
        self::assertFalse($field->isMultiLine());
    }
}
