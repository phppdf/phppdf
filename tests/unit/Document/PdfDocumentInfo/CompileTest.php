<?php

declare(strict_types=1);

namespace PhpPdf\Document\PdfDocumentInfo;

use DateTimeImmutable;
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
#[CoversMethod(PdfDocumentInfo::class, 'compile')]
#[UsesClass(PdfDate::class)]
#[UsesClass(PdfDictionary::class)]
#[UsesClass(PdfName::class)]
#[UsesClass(PdfString::class)]
final class CompileTest extends TestCase
{
    #[Test]
    public function compileReturnsPdfDictionary(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        self::assertInstanceOf(PdfDictionary::class, $dict);
    }

    #[Test]
    public function compileAlwaysIncludesProducer(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        $producer = $dict->get('Producer');
        self::assertInstanceOf(PdfString::class, $producer);
        self::assertSame('phppdf/phppdf', $producer->getValue());
    }

    #[Test]
    public function compileAlwaysIncludesCreationDate(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        self::assertNotNull($dict->get('CreationDate'));
    }

    #[Test]
    public function compileIncludesTitleWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->title('My Report');

        // Act
        $dict = $info->compile();

        // Assert
        $title = $dict->get('Title');
        self::assertInstanceOf(PdfString::class, $title);
        self::assertSame('My Report', $title->getValue());
    }

    #[Test]
    public function compileOmitsTitleWhenNotSet(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        self::assertNull($dict->get('Title'));
    }

    #[Test]
    public function compileIncludesModDateWhenSet(): void
    {
        // Arrange
        $date = new DateTimeImmutable('2024-01-01');
        $info = (new PdfDocumentInfo())->modificationDate($date);

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('ModDate'));
    }

    #[Test]
    public function compileOmitsModDateWhenNotSet(): void
    {
        // Arrange / Act
        $dict = (new PdfDocumentInfo())->compile();

        // Assert
        self::assertNull($dict->get('ModDate'));
    }

    #[Test]
    public function compileIncludesAuthorWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->author('Jane Smith');

        // Act
        $dict = $info->compile();

        // Assert
        $author = $dict->get('Author');
        self::assertInstanceOf(PdfString::class, $author);
        self::assertSame('Jane Smith', $author->getValue());
    }

    #[Test]
    public function compileIncludesSubjectWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->subject('Finance');

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('Subject'));
    }

    #[Test]
    public function compileIncludesKeywordsWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->keywords('pdf, report');

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('Keywords'));
    }

    #[Test]
    public function compileIncludesCreatorWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->creator('MyApp');

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('Creator'));
    }

    #[Test]
    public function compileIncludesTrappedWhenSet(): void
    {
        // Arrange
        $info = (new PdfDocumentInfo())->trapped(PdfTrapped::Trapped);

        // Act
        $dict = $info->compile();

        // Assert
        self::assertNotNull($dict->get('Trapped'));
    }
}
