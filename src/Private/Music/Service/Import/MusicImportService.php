<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Enum\MusicImportSourceType;
use App\Private\Music\Enum\MusicImportStatus;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Archive\SpotifyArchiveReader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class MusicImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MusicRepository $musicRepository,
        private readonly SpotifyArchiveReader $archiveReader,
        private readonly MusicImportBatchWriter $batchWriter,
    ) {
    }

    /**
     * @return array{import: array<string, mixed>, summary: array<string, mixed>}
     */
    public function importSpotifyArchive(UploadedFile $uploadedFile): array
    {
        $plan = $this->archiveReader->readUploadedArchive($uploadedFile);

        $existingImport = $this->musicRepository->findImportByChecksum($plan->getArchiveChecksum());
        if ($existingImport instanceof MusicImport) {
            $plan->collectSummary();

            return [
                'import' => $this->decorateImport($existingImport),
                'summary' => $plan->toSummary(true),
            ];
        }

        $import = $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($plan): MusicImport {
            $import = new MusicImport(
                $this->generateId('music_import'),
                $plan->getOriginalFilename(),
                $plan->getArchiveChecksum(),
            );
            $import->setSourceType(MusicImportSourceType::SpotifyArchive);
            $import->setStatus(MusicImportStatus::Completed);

            $entityManager->persist($import);

            foreach ($plan->iterateEvents() as $eventRow) {
                $this->batchWriter->persistListeningEvent($import, $eventRow);
            }

            $this->batchWriter->finish();

            $import->setSummary($plan->toSummary());
            $entityManager->persist($import);
            $entityManager->flush();

            return $import;
        });

        return [
            'import' => $this->decorateImport($import),
            'summary' => $plan->toSummary(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateImport(MusicImport $import): array
    {
        return [
            'id' => $import->getId(),
            'original_filename' => $import->getOriginalFilename(),
            'source_type' => $import->getSourceType()->value,
            'source_type_label' => $import->getSourceType()->label(),
            'archive_checksum' => $import->getArchiveChecksum(),
            'status' => $import->getStatus()->value,
            'status_label' => $import->getStatus()->label(),
            'imported_at' => $import->getImportedAt(),
            'summary' => $import->getSummary(),
            'error_message' => $import->getErrorMessage(),
        ];
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}
