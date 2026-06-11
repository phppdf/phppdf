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
#[CoversMethod(PdfFormField::class, 'isReadOnly')]
final class IsReadOnlyTest extends TestCase
{
    #[Test]
    public function returnsTrueWhenReadOnly(): void
    {
        // Arrange
        $field = new PdfFormField(
            objectNumber: 1,
            generationNumber: 0,
            name: 'field',
            fullName: 'field',
            type: PdfFormFieldType::Text,
            value: null,
            readOnly: true,
        );

        // Act / Assert
        self::assertTrue($field->isReadOnly());
    }

    #[Test]
    public function returnsFalseWhenNotReadOnly(): void
    {
        // Arrange
        $field = new PdfFormField(
            objectNumber: 1,
            generationNumber: 0,
            name: 'field',
            fullName: 'field',
            type: PdfFormFieldType::Text,
            value: null,
            readOnly: false,
        );

        // Act / Assert
        self::assertFalse($field->isReadOnly());
    }
}
