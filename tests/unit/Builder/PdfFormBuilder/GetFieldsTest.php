<?php

declare(strict_types=1);

namespace PhpPdf\Builder\PdfFormBuilder;

use PhpPdf\Builder\PdfFormBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfFormBuilder::class)]
#[CoversMethod(PdfFormBuilder::class, 'getFields')]
final class GetFieldsTest extends TestCase
{
    #[Test]
    public function getFieldsReturnsEmptyArrayByDefault(): void
    {
        // Arrange / Act
        $form = new PdfFormBuilder();

        // Assert
        self::assertSame([], $form->getFields());
    }

    #[Test]
    public function getFieldsReturnsAllAddedFields(): void
    {
        // Arrange
        $form = new PdfFormBuilder();
        $form->textField('first', 0, 0, 100, 20);
        $form->checkbox('second', 0, 0);

        // Act
        $fields = $form->getFields();

        // Assert
        self::assertCount(2, $fields);
    }
}
