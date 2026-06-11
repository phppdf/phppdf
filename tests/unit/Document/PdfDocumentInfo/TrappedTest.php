<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use PhpPdf\Document\PdfDocumentInfo;
use PhpPdf\Document\PdfTrapped;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PdfDocumentInfo::class)]
#[CoversMethod(PdfDocumentInfo::class, 'trapped')]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class TrappedTest extends TestCase
{
    #[Test]
    public function trappedReturnsSelf(): void
    {
        // Arrange
        $info = new PdfDocumentInfo();

        // Act
        $result = $info->trapped(PdfTrapped::Trapped);

        // Assert
        self::assertSame($info, $result);
    }

    #[Test]
    public function trappedValueAppearsInCompiledDictionary(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->trapped(PdfTrapped::NotTrapped);

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('Trapped'));
    }

    #[Test]
    public function trappedEntryIsAbsentWhenNotSet(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        self::assertNull($dict->get('Trapped'));
    }
}
