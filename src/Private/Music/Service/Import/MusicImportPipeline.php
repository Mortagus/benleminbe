<?php

declare(strict_types=1);

namespace App\Private\Music\Service\Import;

use App\Private\Music\Dto\MusicImportBatch;
use App\Private\Music\Dto\SpotifyArchiveInspection;
use App\Private\Music\Entity\MusicImport;
use App\Private\Music\Enum\MusicImportSourceType;
use App\Private\Music\Enum\MusicImportStatus;
use App\Private\Music\Repository\MusicRepository;
use App\Private\Music\Service\Archive\SpotifyArchiveStreamFactory;
use App\Private\Music\Service\Archive\SpotifyStreamingHistoryReader;
use App\Private\Music\Service\Normalization\MusicNormalizationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class MusicImportPipeline
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MusicRepository $musicRepository,
        private readonly SpotifyArchiveStreamFactory $streamFactory,
        private readonly SpotifyStreamingHistoryReader $streamingHistoryReader,
        private readonly MusicImportBatchWriter $batchWriter,
        private readonly MusicNormalizationService $normalizationService,
        private readonly LoggerInterface $logger,
        #[Autowire('%app.music_import_batch_size%')]
        private readonly int $batchSize = 100,
    ) {
    }

    public function import(SpotifyArchiveInspection $inspection): void
    {
        $this->extendExecutionWindow();

        $referenceIndex = new MusicReferenceIndex($this->musicRepository);
        $referenceIndex->prime();

        $metrics = new MusicImportMetrics();
        $metrics->registerInspection($inspection);
        $metrics->setBatchSize($this->batchSize);
        $metrics->start();

        $importId = $this->generateId('music_import');
        $import = new MusicImport(
            $importId,
            $inspection->getOriginalFilename(),
            $inspection->getArchiveChecksum(),
        );
        $import->setSourceType(MusicImportSourceType::SpotifyArchive);
        $import->setStatus(MusicImportStatus::Processing);

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        $zip = $this->streamFactory->openArchive($inspection->getArchivePath());
        $batchSequence = 0;

        try {
            foreach ($inspection->getMusicFileNames() as $fileName) {
                $batchEvents = [];
                $metrics->startMusicFile($fileName);

                foreach ($this->streamingHistoryReader->iterateListeningEvents($zip, $fileName, $metrics) as $event) {
                    $batchEvents[] = $event;

                    if (count($batchEvents) >= $this->batchSize) {
                        $this->flushBatch($importId, $fileName, ++$batchSequence, $batchEvents, $referenceIndex, $inspection, $metrics);
                        $batchEvents = [];
                    }
                }

                if ($batchEvents !== []) {
                    $this->flushBatch($importId, $fileName, ++$batchSequence, $batchEvents, $referenceIndex, $inspection, $metrics);
                }
            }

            $metrics->finish();
            $summary = $metrics->toSummary($this->normalizationService);
            $this->storeImportState($importId, MusicImportStatus::Completed, $summary, null);
            $this->entityManager->clear();
        } catch (\Throwable $exception) {
            $metrics->finish();
            $summary = $metrics->toSummary($this->normalizationService);
            $this->storeImportState($importId, MusicImportStatus::Failed, $summary, $this->buildFailureMessage($exception));
            $this->entityManager->clear();

            $this->logger->error('Music import failed.', [
                'import_id' => $importId,
                'archive_checksum' => $inspection->getArchiveChecksum(),
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        } finally {
            $zip->close();
        }
    }

    private function extendExecutionWindow(): void
    {
        if (function_exists('ignore_user_abort')) {
            ignore_user_abort(true);
        }

        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }
    }

    /**
     * @param list<\App\Private\Music\Dto\ParsedListeningEvent> $batchEvents
     */
    private function flushBatch(
        string $importId,
        string $sourceFileName,
        int $batchSequence,
        array $batchEvents,
        MusicReferenceIndex $referenceIndex,
        SpotifyArchiveInspection $inspection,
        MusicImportMetrics $metrics,
    ): void {
        $batch = new MusicImportBatch($sourceFileName, $batchSequence, $batchEvents);

        $this->entityManager->wrapInTransaction(function () use ($batch, $importId, $referenceIndex, $inspection, $metrics): void {
            $this->batchWriter->writeBatch(
                $batch,
                $importId,
                $referenceIndex,
                $inspection->getLibraryIndex(),
                $metrics,
            );
        });
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }

    private function buildFailureMessage(\Throwable $exception): string
    {
        $message = trim($exception->getMessage());
        if ($message === '') {
            $message = $exception::class;
        }

        return mb_substr($message, 0, 1000);
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function storeImportState(string $importId, MusicImportStatus $status, array $summary, ?string $errorMessage): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE music_imports SET status = :status, summary = :summary, error_message = :error_message WHERE id = :id',
            [
                'status' => $status->value,
                'summary' => json_encode($summary, JSON_THROW_ON_ERROR),
                'error_message' => $errorMessage,
                'id' => $importId,
            ],
        );
    }
}
