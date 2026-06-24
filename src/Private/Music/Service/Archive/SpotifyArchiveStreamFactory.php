<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Archive;

use InvalidArgumentException;
use ZipArchive;

final class SpotifyArchiveStreamFactory
{
    public function openArchive(string $archivePath): ZipArchive
    {
        $zip = new ZipArchive();
        $openResult = $zip->open($archivePath);
        if ($openResult !== true) {
            throw new InvalidArgumentException('Le fichier ZIP importe ne peut pas etre ouvert.');
        }

        return $zip;
    }

    /**
     * @return resource
     */
    public function openEntryStream(ZipArchive $zip, string $entryName)
    {
        $stream = $zip->getStream($entryName);
        if (!is_resource($stream)) {
            throw new InvalidArgumentException(sprintf('Le fichier "%s" est illisible dans le ZIP.', $entryName));
        }

        return $stream;
    }
}
