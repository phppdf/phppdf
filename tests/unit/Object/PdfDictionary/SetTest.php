<?php

declare(strict_types=1);

namespace PhpPdf\Object\PdfDictionary;

use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDictionary::class)]
#[CoversMethod(PdfDictionary::class, 'set')]
#[UsesClass(PdfName::class)]
final class SetTest extends TestCase
{
    #[Test]
    public function setAddsNewEntry(): void
    {
        // Arrange
        $dict = new PdfDictionary();
        $name = new PdfName('Page');

        // Act
        $dict->set('Type', $name);

        // Assert
        self::assertSame($name, $dict->get('Type'));
    }

    #[Test]
    public function setReplacesExistingEntry(): void
    {
        // Arrange
        $dict = new PdfDictionary(['Type' => new PdfName('Old')]);
        $newName = new PdfName('New');

        // Act
        $dict->set('Type', $newName);

        // Assert
        self::assertSame($newName, $dict->get('Type'));
    }
}
