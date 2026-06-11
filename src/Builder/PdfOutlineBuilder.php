<?php

declare(strict_types=1);

namespace PhpPdf\Builder;

/**
 * Fluent builder for the PDF document outline (bookmarks).
 *
 * Pass a configured instance (or a callable that receives one) to
 * PdfDocumentBuilder::outline(). Items reference pages by their 0-based index
 * in the order they were added to the document builder. Nesting is supported
 * to any depth via the optional $configure callback on item().
 *
 * Example:
 *
 *   $builder->outline(function (PdfOutlineBuilder $o): void {
 *       $o->item('Introduction', 0);
 *       $o->item('Chapter 1', 1, function (PdfOutlineBuilder $c): void {
 *           $c->item('Section 1.1', 1);
 *           $c->item('Section 1.2', 2);
 *       });
 *       $o->item('Conclusion', 3);
 *   });
 */
final class PdfOutlineBuilder
{
    /** @var list<\PhpPdf\Builder\PdfOutlineItemSpec> */
    private array $items = [];

    /**
     * Adds a bookmark that navigates to the given 0-based page index.
     *
     * Pass a $configure callable to add child items under this entry.
     *
     * @param callable(\PhpPdf\Builder\PdfOutlineBuilder): void|null $configure
     */
    public function item(string $title, int $pageIndex, ?callable $configure = null): self
    {
        $children = new self();

        if ($configure !== null) {
            $configure($children);
        }

        $this->items[] = new PdfOutlineItemSpec($title, $pageIndex, $children);

        return $this;
    }

    /** @return list<\PhpPdf\Builder\PdfOutlineItemSpec> */
    public function getItems(): array
    {
        return $this->items;
    }
}
