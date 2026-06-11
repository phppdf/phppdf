<?php

declare(strict_types=1);

namespace PhpPdf\Document;

use DateTimeImmutable;
use DateTimeInterface;
use PhpPdf\Object\PdfDate;
use PhpPdf\Object\PdfDictionary;
use PhpPdf\Object\PdfName;
use PhpPdf\Object\PdfString;

/**
 * Fluent builder for the PDF document information dictionary.
 *
 * Covers all standard entries defined in the PDF specification (Table 317).
 * Every field is optional except the producer and creation date, which are
 * set automatically. Pass a configured instance to PdfDocumentBuilder::info().
 *
 * Example:
 *
 *   $builder->info(
 *       (new PdfDocumentInfo())
 *           ->title('Annual Report 2024')
 *           ->author('Jane Smith')
 *           ->subject('Financial summary for the fiscal year 2024')
 *           ->keywords('annual report, finance, 2024')
 *           ->creator('ReportApp 3.0')
 *   );
 */
final class PdfDocumentInfo
{
    /**
     * The document's title, displayed in the viewer's title bar.
     */
    private ?string $title = null;

    /**
     * The name of the person who created the document.
     */
    private ?string $author = null;

    /**
     * The subject of the document.
     */
    private ?string $subject = null;

    /**
     * Keywords associated with the document, typically space- or
     * comma-separated, used by search engines and document management systems.
     */
    private ?string $keywords = null;

    /**
     * The name of the application that created the original document before
     * it was converted to PDF. Distinct from the producer.
     */
    private ?string $creator = null;

    /**
     * The name of the application that converted the document to PDF.
     * Defaults to 'phppdf/phppdf'.
     */
    private string $producer = 'phppdf/phppdf';

    /**
     * The date and time the document was created. Defaults to the current instant.
     */
    private DateTimeInterface $creationDate;

    /**
     * The date and time the document was most recently modified.
     */
    private ?DateTimeInterface $modificationDate = null;

    /**
     * Whether the document has been modified to include trapping information.
     * When null the Trapped entry is omitted from the dictionary.
     */
    private ?PdfTrapped $trapped = null;

    public function __construct()
    {
        $this->creationDate = new DateTimeImmutable();
    }

    /**
     * Sets the document title shown in the viewer's title bar and properties panel.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Sets the name of the person who created the document.
     */
    public function author(string $author): self
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Sets a short description of the document's subject.
     */
    public function subject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Sets the keywords associated with the document.
     *
     * Typically a space- or comma-separated list of terms used by search
     * engines and document management systems to index the document.
     */
    public function keywords(string $keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Sets the name of the application that created the source document.
     *
     * Use this when the PDF was generated from another format (e.g. a word
     * processor or design tool). Distinct from the producer, which identifies
     * the PDF-generation library.
     */
    public function creator(string $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    /**
     * Overrides the producer string identifying the PDF-generation software.
     *
     * Defaults to 'phppdf/phppdf'. Override this when embedding this library
     * inside a larger application that should be credited as the producer.
     */
    public function producer(string $producer): self
    {
        $this->producer = $producer;

        return $this;
    }

    /**
     * Sets the document creation date.
     *
     * Defaults to the current instant. Override this when reproducing or
     * re-generating a document that has a canonical creation date.
     */
    public function creationDate(DateTimeInterface $date): self
    {
        $this->creationDate = $date;

        return $this;
    }

    /**
     * Sets the date and time the document was most recently modified.
     *
     * Should be updated whenever the document content changes. When omitted
     * the ModDate entry is not written to the Info dictionary.
     */
    public function modificationDate(DateTimeInterface $date): self
    {
        $this->modificationDate = $date;

        return $this;
    }

    /**
     * Sets the trapping status of the document.
     *
     * Use PdfTrapped::Trapped when the document is fully trapped,
     * PdfTrapped::NotTrapped when it has not been trapped, or
     * PdfTrapped::Unknown when the trapping state is not known.
     * When not called the Trapped entry is omitted from the dictionary.
     */
    public function trapped(PdfTrapped $trapped): self
    {
        $this->trapped = $trapped;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAuthor(): ?string
    {
        return $this->author;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function getKeywords(): ?string
    {
        return $this->keywords;
    }

    public function getCreator(): ?string
    {
        return $this->creator;
    }

    public function getProducer(): string
    {
        return $this->producer;
    }

    public function getCreationDate(): DateTimeInterface
    {
        return $this->creationDate;
    }

    public function getModificationDate(): ?DateTimeInterface
    {
        return $this->modificationDate;
    }

    /**
     * Compiles the configured fields into a PdfDictionary.
     *
     * Called internally by PdfDocumentBuilder::build(). Only fields that have
     * been explicitly set are included; the producer and creation date are
     * always present.
     */
    public function compile(): PdfDictionary
    {
        $entries = [];

        if ($this->title !== null) {
            $entries['Title'] = new PdfString($this->title);
        }

        if ($this->author !== null) {
            $entries['Author'] = new PdfString($this->author);
        }

        if ($this->subject !== null) {
            $entries['Subject'] = new PdfString($this->subject);
        }

        if ($this->keywords !== null) {
            $entries['Keywords'] = new PdfString($this->keywords);
        }

        if ($this->creator !== null) {
            $entries['Creator'] = new PdfString($this->creator);
        }

        $entries['Producer'] = new PdfString($this->producer);
        $entries['CreationDate'] = new PdfDate($this->creationDate);

        if ($this->modificationDate !== null) {
            $entries['ModDate'] = new PdfDate($this->modificationDate);
        }

        if ($this->trapped !== null) {
            $entries['Trapped'] = new PdfName($this->trapped->value);
        }

        return new PdfDictionary($entries);
    }
}
