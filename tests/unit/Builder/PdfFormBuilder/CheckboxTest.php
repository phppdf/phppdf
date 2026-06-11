<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfFormBuilder;

use PhpPdf\Builder\PdfFormBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormBuilder::class)]
#[CoversMethod(PdfFormBuilder::class, 'checkbox')]
final class CheckboxTest extends TestCase
{
    #[Test]
    public function checkboxReturnsSelf(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $result = $form->checkbox('agree', 10, 20);

        // Assert
        self::assertSame($form, $result);
    }

    #[Test]
    public function checkboxStoresFieldWithCorrectType(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->checkbox('agree', 10, 20);

        // Assert
        $fields = $form->getFields();
        self::assertCount(1, $fields);
        self::assertSame('checkbox', $fields[0]['type']);
        self::assertSame('agree', $fields[0]['name']);
    }

    #[Test]
    public function checkboxDefaultIsUnchecked(): void
    {
        // Arrange
        $form = new PdfFormBuilder();

        // Act
        $form->checkbox('agree', 10, 20);

        // Assert
        self::assertFalse($form->getFields()[0]['checked']);
    }
}
