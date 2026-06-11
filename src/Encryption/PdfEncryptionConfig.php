<?php

declare(strict_types=1);

namespace PhpPdf\Encryption;

/**
 * Configuration for PDF standard security handler encryption (V=4, R=4, AES-128).
 *
 * Pass a configured instance to PdfDocumentBuilder::encrypt(). The builder
 * computes the O/U entries and encryption key during build(), then the
 * serializer encrypts every string and stream in the document.
 *
 * Example — password-protect with print-only access:
 *
 *   $builder->encrypt(
 *       (new PdfEncryptionConfig())
 *           ->userPassword('open')
 *           ->ownerPassword('admin')
 *           ->permissions(PdfPermissions::none()->allowPrinting())
 *   );
 */
final class PdfEncryptionConfig
{
    private string $userPassword = '';
    private string $ownerPassword = '';
    private PdfPermissions $permissions;

    public function __construct()
    {
        $this->permissions = PdfPermissions::all();
    }

    /**
     * Password required to open the document. Empty string means no password needed.
     */
    public function userPassword(string $password): self
    {
        $this->userPassword = $password;

        return $this;
    }

    /**
     * Password required to change security settings or override permissions.
     * Defaults to the user password when not set.
     */
    public function ownerPassword(string $password): self
    {
        $this->ownerPassword = $password;

        return $this;
    }

    /**
     * Restrict what users can do with the document.
     */
    public function permissions(PdfPermissions $permissions): self
    {
        $this->permissions = $permissions;

        return $this;
    }

    public function getUserPassword(): string
    {
        return $this->userPassword;
    }

    public function getOwnerPassword(): string
    {
        // Fall back to user password when no owner password is set.
        return $this->ownerPassword !== ''
            ? $this->ownerPassword
            : $this->userPassword;
    }

    public function getPermissions(): PdfPermissions
    {
        return $this->permissions;
    }
}
