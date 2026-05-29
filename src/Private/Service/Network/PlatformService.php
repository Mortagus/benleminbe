<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Platform;
use App\Enum\Network\PlatformStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class PlatformService
{
    /**
     * @var array<int, array{slug: string, name: string, category: string, profile_url: string, status: string, note: string, last_reviewed_at: string|null, active: bool}>
     */
    private const array DEFAULT_PLATFORMS = [
        [
            'slug' => 'linkedin',
            'name' => 'LinkedIn',
            'category' => 'reseau',
            'profile_url' => 'https://www.linkedin.com/in/benlem/',
            'status' => 'a_jour',
            'note' => 'Profil professionnel principal.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'malt',
            'name' => 'Malt',
            'category' => 'freelance',
            'profile_url' => 'https://fr.malt.be/profile/benjaminlemin',
            'status' => 'a_jour',
            'note' => 'Canal principal pour les missions freelance structurées.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'indeed',
            'name' => 'Indeed',
            'category' => 'jobboard',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => 'À renseigner si un profil est ouvert ou à créer.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'lehibou',
            'name' => 'LeHibou',
            'category' => 'freelance',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => 'À renseigner si un profil existe ou doit être créé.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'wiggli',
            'name' => 'Wiggli',
            'category' => 'freelance',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => 'À renseigner si un profil existe ou doit être créé.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'superprof',
            'name' => 'Superprof',
            'category' => 'coaching',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => 'Plateforme de coaching technique à suivre séparément.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
        [
            'slug' => 'apprentus',
            'name' => 'Apprentus',
            'category' => 'coaching',
            'profile_url' => '',
            'status' => 'a_enrichir',
            'note' => 'Plateforme de coaching technique à suivre séparément.',
            'last_reviewed_at' => null,
            'active' => true,
        ],
    ];

    private bool $seeded = false;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
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

        foreach (self::DEFAULT_PLATFORMS as $data) {
            $platform = new Platform();
            $platform->setSlug($data['slug']);
            $platform->setName($data['name']);
            $platform->setCategory($data['category']);
            $platform->setProfileUrl($data['profile_url'] !== '' ? $data['profile_url'] : null);
            $platform->setStatus($this->platformStatusFromValue($data['status']));
            $platform->setNote($data['note']);
            $platform->setLastReviewedAt($this->parseDate($data['last_reviewed_at']));
            $platform->setActive($data['active']);
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
