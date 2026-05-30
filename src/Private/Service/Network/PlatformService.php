<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Platform;
use App\Enum\Network\PlatformStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PlatformService
{
    private const int PLATFORM_BACKUP_SCHEMA_VERSION = 1;

    private bool $seeded = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlatforms(string $query = ''): array
    {
        $this->ensureSeeded();

        $platforms = $this->decoratePlatforms($this->loadPlatforms());
        $query = mb_strtolower(trim($query));

        if ($query === '') {
            return $this->sortPlatforms($platforms);
        }

        $platforms = array_values(array_filter($platforms, static function (array $platform) use ($query): bool {
            $haystack = implode(' ', [
                $platform['name'],
                $platform['category'],
                $platform['status_label'],
                $platform['note'],
                $platform['profile_url'],
            ]);

            return str_contains(mb_strtolower($haystack), $query);
        }));

        return $this->sortPlatforms($platforms);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlatform(string $slug): array
    {
        $this->ensureSeeded();

        $platform = $this->entityManager->getRepository(Platform::class)->find($slug);
        if (!$platform instanceof Platform) {
            throw new NotFoundHttpException(sprintf('Platform "%s" was not found.', $slug));
        }

        return $this->decoratePlatform($platform);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function savePlatform(array $payload, ?string $existingSlug = null): array
    {
        $this->ensureSeeded();

        $platforms = $this->loadPlatforms();
        $data = $this->normalizePlatformPayload($payload, $existingSlug, $platforms);

        $platform = $existingSlug !== null
            ? $this->entityManager->getRepository(Platform::class)->find($existingSlug)
            : null;

        if (!$platform instanceof Platform) {
            $platform = new Platform();
        }

        $this->applyPlatformData($platform, $data);
        $this->entityManager->persist($platform);
        $this->entityManager->flush();

        return $this->decoratePlatform($platform);
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultValues(): array
    {
        return [
            'slug' => '',
            'name' => '',
            'category' => 'reseau',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => '',
            'last_reviewed_at' => '',
            'active' => true,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getStatusOptions(): array
    {
        return PlatformStatus::labels();
    }

    /**
     * @return array{schema_version: int, exported_at: string, platforms: list<array<string, mixed>>}
     */
    public function exportPlatformsBackup(): array
    {
        $this->ensureSeeded();
        $platforms = array_map(
            fn (Platform $platform): array => $this->platformToBackupRecord($platform),
            $this->loadPlatforms(),
        );

        return [
            'schema_version' => self::PLATFORM_BACKUP_SCHEMA_VERSION,
            'exported_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'platforms' => $this->sortPlatforms($platforms),
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{imported: int, replaced: int, total: int}
     */
    public function importPlatformsBackup(array $payload): array
    {
        $platformRecords = $this->extractBackupRecords($payload);
        $normalizedRecords = $this->normalizeBackupRecords($platformRecords);

        return $this->replaceAllPlatforms($normalizedRecords);
    }

    /**
     * @return list<Platform>
     */
    private function loadPlatforms(): array
    {
        return $this->entityManager->getRepository(Platform::class)->findAll();
    }

    private function ensureSeeded(): void
    {
        if ($this->seeded) {
            return;
        }

        $this->seeded = true;

        if ($this->entityManager->getRepository(Platform::class)->count([]) > 0) {
            return;
        }

        foreach ($this->loadDefaultPlatformRecords() as $data) {
            $platform = new Platform();
            $this->applyPlatformData($platform, $data);
            $this->entityManager->persist($platform);
        }

        $this->entityManager->flush();
    }

    /**
     * @param list<Platform> $platforms
     *
     * @return list<array<string, mixed>>
     */
    private function decoratePlatforms(array $platforms): array
    {
        return array_map(fn (Platform $platform): array => $this->decoratePlatform($platform), $platforms);
    }

    /**
     * @return array<string, mixed>
     */
    private function decoratePlatform(Platform $platform): array
    {
        return [
            'slug' => $platform->getSlug(),
            'name' => $platform->getName(),
            'category' => $platform->getCategory(),
            'profile_url' => $platform->getProfileUrl() ?? '',
            'status' => $platform->getStatus()->value,
            'status_label' => $platform->getStatusLabel(),
            'note' => $platform->getNote() ?? '',
            'last_reviewed_at' => $this->formatDate($platform->getLastReviewedAt()),
            'active' => $platform->isActive(),
        ];
    }

    /**
     * @param list<array<string, mixed>> $platforms
     *
     * @return list<array<string, mixed>>
     */
    private function sortPlatforms(array $platforms): array
    {
        usort($platforms, static function (array $left, array $right): int {
            if ($left['active'] !== $right['active']) {
                return $left['active'] ? -1 : 1;
            }

            return strcasecmp($left['name'], $right['name']);
        });

        return $platforms;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<Platform> $existingPlatforms
     *
     * @return array<string, mixed>
     */
    private function normalizePlatformPayload(array $payload, ?string $existingSlug, array $existingPlatforms): array
    {
        $name = $this->normalizeString($payload['name'] ?? '');
        if ($name === '') {
            throw new InvalidArgumentException('Platform name is required.');
        }

        $slug = $this->normalizeString($payload['slug'] ?? '');
        $slug = $existingSlug !== null ? $existingSlug : ($slug !== '' ? $slug : $this->slugify($name));

        if ($slug === '') {
            throw new InvalidArgumentException('Platform slug could not be generated.');
        }

        $slug = $this->ensureUniquePlatformSlug($slug, $existingSlug, $existingPlatforms);

        return [
            'slug' => $slug,
            'name' => $name,
            'category' => $this->normalizeString($payload['category'] ?? 'reseau'),
            'profile_url' => $this->normalizeString($payload['profile_url'] ?? ''),
            'status' => $this->normalizePlatformStatus($payload['status'] ?? 'a_enrichir'),
            'note' => $this->normalizeString($payload['note'] ?? ''),
            'last_reviewed_at' => $this->normalizeDate($payload['last_reviewed_at'] ?? null),
            'active' => $this->normalizeBoolean($payload['active'] ?? true),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyPlatformData(Platform $platform, array $data): void
    {
        $platform->setSlug($data['slug']);
        $platform->setName($data['name']);
        $platform->setCategory($data['category']);
        $platform->setProfileUrl($data['profile_url'] !== '' ? $data['profile_url'] : null);
        $platform->setStatus($this->platformStatusFromValue($data['status']));
        $platform->setNote($data['note'] !== '' ? $data['note'] : null);
        $platform->setLastReviewedAt($this->parseDate($data['last_reviewed_at']));
        $platform->setActive((bool) $data['active']);
    }

    /**
     * @return array<string, mixed>
     */
    private function platformToBackupRecord(Platform $platform): array
    {
        return [
            'slug' => $platform->getSlug(),
            'name' => $platform->getName(),
            'category' => $platform->getCategory(),
            'profile_url' => $platform->getProfileUrl() ?? '',
            'status' => $platform->getStatus()->value,
            'note' => $platform->getNote() ?? '',
            'last_reviewed_at' => $this->formatDate($platform->getLastReviewedAt()),
            'active' => $platform->isActive(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadDefaultPlatformRecords(): array
    {
        $backup = $this->readBackupFile();
        $records = $this->extractBackupRecords($backup);

        return $this->normalizeBackupRecords($records);
    }

    /**
     * @return array<string, mixed>
     */
    private function readBackupFile(): array
    {
        $path = $this->getBackupFilePath();

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Platform backup file not found at "%s".', $path));
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read platform backup file at "%s".', $path));
        }

        try {
            $payload = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('The platform backup file contains invalid JSON.', 0, $exception);
        }

        if (!is_array($payload)) {
            throw new InvalidArgumentException('The platform backup file must decode to an array.');
        }

        return $payload;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function extractBackupRecords(array $payload): array
    {
        if (array_is_list($payload)) {
            return $payload;
        }

        $schemaVersion = $payload['schema_version'] ?? null;
        if ($schemaVersion !== null && (int) $schemaVersion !== self::PLATFORM_BACKUP_SCHEMA_VERSION) {
            throw new InvalidArgumentException(sprintf('Unsupported platform backup schema version "%s".', (string) $schemaVersion));
        }

        if (!array_key_exists('platforms', $payload) || !is_array($payload['platforms'])) {
            throw new InvalidArgumentException('The platform backup must contain a "platforms" list.');
        }

        /** @var list<array<string, mixed>> $records */
        $records = $payload['platforms'];

        return $records;
    }

    /**
     * @param list<array<string, mixed>> $records
     *
     * @return list<array<string, mixed>>
     */
    private function normalizeBackupRecords(array $records): array
    {
        $normalized = [];
        $seenSlugs = [];

        foreach ($records as $index => $record) {
            if (!is_array($record)) {
                throw new InvalidArgumentException(sprintf('Platform entry #%d is not valid.', $index + 1));
            }

            $data = $this->normalizeBackupRecord($record, $index + 1);
            if (isset($seenSlugs[$data['slug']])) {
                throw new InvalidArgumentException(sprintf('Duplicate platform slug "%s" found in backup.', $data['slug']));
            }

            $seenSlugs[$data['slug']] = true;
            $normalized[] = $data;
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $record
     *
     * @return array<string, mixed>
     */
    private function normalizeBackupRecord(array $record, int $position): array
    {
        $slug = $this->normalizeString($record['slug'] ?? '');
        if ($slug === '') {
            throw new InvalidArgumentException(sprintf('Platform entry #%d is missing a slug.', $position));
        }

        $name = $this->normalizeString($record['name'] ?? '');
        if ($name === '') {
            throw new InvalidArgumentException(sprintf('Platform entry "%s" is missing a name.', $slug));
        }

        return [
            'slug' => $slug,
            'name' => $name,
            'category' => $this->normalizeString($record['category'] ?? 'reseau'),
            'profile_url' => $this->normalizeString($record['profile_url'] ?? ''),
            'status' => $this->normalizeBackupStatus($record['status'] ?? PlatformStatus::default()->value, $position),
            'note' => $this->normalizeString($record['note'] ?? ''),
            'last_reviewed_at' => $this->normalizeBackupDate($record['last_reviewed_at'] ?? null, $position, 'last_reviewed_at'),
            'active' => $this->normalizeBoolean($record['active'] ?? true),
        ];
    }

    /**
     * @param list<array<string, mixed>> $platformsData
     *
     * @return array{imported: int, replaced: int, total: int}
     */
    private function replaceAllPlatforms(array $platformsData): array
    {
        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use ($platformsData): array {
            $entityManager->clear();
            $connection = $entityManager->getConnection();
            $replaced = (int) $connection->executeStatement('DELETE FROM network_platforms');

            foreach ($platformsData as $data) {
                $platform = new Platform();
                $this->applyPlatformData($platform, $data);
                $entityManager->persist($platform);
            }

            $entityManager->flush();

            return [
                'imported' => count($platformsData),
                'replaced' => $replaced,
                'total' => count($platformsData),
            ];
        });
    }

    private function getBackupFilePath(): string
    {
        return rtrim($this->projectDir, '/\\') . '/data/private/network/platforms.json';
    }

    private function normalizeBackupStatus(mixed $status, int $position): string
    {
        $status = $this->normalizeString((string) $status);
        if ($status === '' || PlatformStatus::tryFrom($status) === null) {
            throw new InvalidArgumentException(sprintf('Platform entry #%d has an invalid status.', $position));
        }

        return $status;
    }

    private function normalizeBackupDate(mixed $value, int $position, string $field): ?string
    {
        $value = $this->normalizeString((string) $value);
        if ($value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException(sprintf('Platform entry #%d has an invalid %s value.', $position, $field));
        }

        return $date->format('Y-m-d');
    }

    private function findPlatformIndex(array $platforms, string $slug): ?int
    {
        foreach ($platforms as $index => $platform) {
            if ($platform instanceof Platform && $platform->getSlug() === $slug) {
                return $index;
            }
        }

        return null;
    }

    private function ensureUniquePlatformSlug(string $slug, ?string $existingSlug, array $platforms): string
    {
        $candidate = $slug;
        $suffix = 2;

        while (true) {
            $index = $this->findPlatformIndex($platforms, $candidate);
            if ($index === null || $candidate === $existingSlug) {
                return $candidate;
            }

            $candidate = sprintf('%s-%d', $slug, $suffix);
            $suffix++;
        }
    }

    private function platformStatusFromValue(mixed $status): PlatformStatus
    {
        $status = $this->normalizeString((string) $status);

        return PlatformStatus::tryFrom($status) ?? PlatformStatus::default();
    }

    private function normalizePlatformStatus(mixed $status): string
    {
        return $this->platformStatusFromValue($status)->value;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 'on', 'yes', 'oui'], true);
        }

        return (bool) $value;
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    private function normalizeDate(mixed $value): ?string
    {
        $value = $this->normalizeString((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return (new DateTimeImmutable($value))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        $date = $this->normalizeDate($value);
        if ($date === null) {
            return null;
        }

        try {
            return new DateTimeImmutable($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format('Y-m-d') : null;
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value;
    }
}
