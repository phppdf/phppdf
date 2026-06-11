<?php

declare(strict_types=1);

namespace PhpPdf\Html\Internal;

/**
 * Discriminator for the different kinds of layout blocks produced by
 * HtmlLayoutEngine during the DOM-walking phase.
 */
enum LayoutBlockType
{
    /** A paragraph of flowing text (p, h1–h6, div, blockquote, …). */
    case Text;

    /** A list of items (ul / ol), rendered with bullet or number markers. */
    case ItemList;

    /** A full-width horizontal rule (<hr>). */
    case HorizontalRule;

    /** An explicit line break (<br>) at block level — contributes vertical space only. */
    case LineBreak;

    /** An HTML <table> element rendered via TableBuilder. */
    case Table;
}
