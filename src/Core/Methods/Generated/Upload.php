<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Core\Methods\Generated;

/**
 * mtproto upload.* curated method builders.
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php from config/curated-methods.json — do not edit by hand.
 */
final class Upload
{
    public function getFile(): UploadGetFileBuilder
    {
        return new UploadGetFileBuilder();
    }
}

/**
 * Fluent builder for upload.getFile (mtproto, return: upload.File).
 * Returns content of a whole file or its part.
 * Docs: https://core.telegram.org/method/upload.getFile
 *
 * @generated 2026-08-28 by bin/generate-method-builders.php — do not edit by hand.
 */
final class UploadGetFileBuilder
{
    /** @var array<string, mixed> */
    private array $p = [];

    public function precise(bool $precise): self
    {
        $this->p['precise'] = $precise;

        return $this;
    }

    public function cdnSupported(bool $cdn_supported): self
    {
        $this->p['cdn_supported'] = $cdn_supported;

        return $this;
    }

    public function location(mixed $location): self
    {
        $this->p['location'] = $location;

        return $this;
    }

    public function offset(int $offset): self
    {
        $this->p['offset'] = $offset;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->p['limit'] = $limit;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toRequest(): array
    {
        $missing = [];
        foreach (['location', 'offset', 'limit'] as $required) {
            if (! array_key_exists($required, $this->p)) {
                $missing[] = $required;
            }
        }

        if ($missing !== []) {
            throw new \InvalidArgumentException('upload.getFile: missing ' . implode(', ', $missing));
        }

        return array_merge(['_' => 'upload.getFile'], $this->p);
    }
}
