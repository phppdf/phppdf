# CLI

The `phppdf` binary is installed to `vendor/bin/phppdf` by Composer.

```bash
vendor/bin/phppdf <command> [options] [arguments]
```

---

## `phppdf text`

Extract plain text from a PDF file and print it to stdout.

```bash
vendor/bin/phppdf text document.pdf
```

**Options**

| Option | Description |
|---|---|
| `--page=N` | Extract only page N (0-based) |

**Example**

```bash
vendor/bin/phppdf text report.pdf --page=0
```

---

## `phppdf info`

Print the PDF version, page count, and /Info dictionary entries.

```bash
vendor/bin/phppdf info document.pdf
```

**Example output**

```
Version    : 1.7
Pages      : 12
Title      : Annual Report 2025
Author     : Jane Smith
Subject    : Financial results
Creator    : My App 1.0
```

---

## `phppdf merge`

Combine two or more PDF files into a single output file.

```bash
vendor/bin/phppdf merge -o merged.pdf file1.pdf file2.pdf file3.pdf
```

**Options**

| Option | Description |
|---|---|
| `-o <path>` | Output file path (required) |

**Example**

```bash
vendor/bin/phppdf merge -o combined.pdf cover.pdf body.pdf appendix.pdf
```

Pages from each file are appended in the order the files are given.  Encryption, bookmarks, and digital signatures from source files are not carried over (see [Merging](operations/merging.md) for the same limitation in the PHP API).
