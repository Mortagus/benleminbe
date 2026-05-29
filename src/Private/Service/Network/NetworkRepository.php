<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ImportLog;
use App\Entity\Network\Interaction;
use App\Entity\Network\Platform;
use App\Enum\Network\ContactImportSource;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use App\Enum\Network\PlatformStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class NetworkRepository
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
     * @return array{
     *     stats: array<string, int>,
     *     platforms: list<array<string, mixed>>,
     *     contacts: list<array<string, mixed>>,
     *     organizations: list<array{organization: string, count: int, last_contact_at: string|null}>,
     *     recent_interactions: list<array<string, mixed>>,
     *     recent_imports: list<array<string, mixed>>
     * }
     */
    public function getDashboardData(): array
    {
        $this->ensureSeeded();

        $platforms = $this->decoratePlatforms($this->loadPlatforms());
        $contacts = $this->decorateContacts($this->loadContacts());
        $interactions = $this->decorateInteractions($this->loadInteractions());
        $imports = $this->decorateImports($this->loadImports());

        return [
            'stats' => [
                'platforms_total' => count($platforms),
                'platforms_configured' => count(array_filter($platforms, static fn (array $platform): bool => $platform['profile_url'] !== '')),
                'contacts_total' => count($contacts),
                'contacts_high_priority' => count(array_filter($contacts, static fn (array $contact): bool => $contact['priority'] === 'haute')),
                'contacts_to_followup' => count(array_filter($contacts, static fn (array $contact): bool => in_array($contact['relationship_status'], ['prioritaire', 'a_relancer'], true))),
            ],
            'platforms' => array_slice($this->sortPlatforms($platforms), 0, 6),
            'contacts' => array_slice($this->sortContacts($contacts), 0, 8),
            'organizations' => array_slice($this->buildOrganizations($contacts), 0, 6),
            'recent_interactions' => array_slice($this->sortInteractions($interactions), 0, 5),
            'recent_imports' => array_slice($this->sortImports($imports), 0, 5),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlatforms(): array
    {
        $this->ensureSeeded();

        return $this->sortPlatforms($this->decoratePlatforms($this->loadPlatforms()));
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
     * @param array<string, mixed> $criteria
     *
     * @return list<array<string, mixed>>
     */
    public function listContacts(array $criteria = []): array
    {
        $contacts = $this->decorateContacts($this->loadContacts());
        $search = $this->normalizeString($criteria['search'] ?? '');
        $priority = $this->normalizeString($criteria['priority'] ?? '');
        $status = $this->normalizeString($criteria['relationship_status'] ?? '');

        $contacts = array_values(array_filter($contacts, static function (array $contact) use ($search, $priority, $status): bool {
            if ($priority !== '' && $contact['priority'] !== $priority) {
                return false;
            }

            if ($status !== '' && $contact['relationship_status'] !== $status) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', array_filter([
                $contact['display_name'],
                $contact['organization'],
                $contact['role'],
                $contact['email'],
                $contact['phone'],
                $contact['profile_url'],
                $contact['notes'],
                implode(' ', $contact['tags']),
            ]));

            return str_contains(mb_strtolower($haystack), $search);
        }));

        return $this->sortContacts($contacts);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContact(string $id): array
    {
        $contact = $this->entityManager->getRepository(Contact::class)->find($id);
        if (!$contact instanceof Contact) {
            throw new NotFoundHttpException(sprintf('Contact "%s" was not found.', $id));
        }

        return $this->decorateContact($contact);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function saveContact(array $payload, ?string $existingId = null): array
    {
        $this->ensureSeeded();

        $contacts = $this->loadContacts();
        $data = $this->normalizeContactPayload($payload, $existingId, $contacts);

        $contactIndex = $existingId !== null
            ? $this->findContactIndex($contacts, $existingId)
            : $this->findMatchingContactIndex($contacts, $data);

        $contact = $contactIndex !== null ? $contacts[$contactIndex] : null;

        if (!$contact instanceof Contact) {
            $contact = new Contact($data['id'], $data['display_name']);
        }

        $this->applyContactData($contact, $data, $contactIndex !== null);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->decorateContact($contact);
    }

    public function deleteContact(string $id): void
    {
        $this->ensureSeeded();

        $contact = $this->entityManager->getRepository(Contact::class)->find($id);
        if (!$contact instanceof Contact) {
            throw new NotFoundHttpException(sprintf('Contact "%s" was not found.', $id));
        }

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
    }

    /**
     * @return array{merged_contacts: int, merged_groups: int, moved_interactions: int}
     */
    public function autoMergeContacts(): array
    {
        $this->ensureSeeded();

        $contacts = $this->loadContacts();
        $clusters = $this->buildAutoMergeClusters($contacts);
        $mergedContacts = 0;
        $mergedGroups = 0;
        $movedInteractions = 0;

        if ($clusters === []) {
            return [
                'merged_contacts' => 0,
                'merged_groups' => 0,
                'moved_interactions' => 0,
            ];
        }

        $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager) use (
            $contacts,
            $clusters,
            &$mergedContacts,
            &$mergedGroups,
            &$movedInteractions,
        ): void {
            foreach ($clusters as $cluster) {
                if (count($cluster) < 2) {
                    continue;
                }

                $canonicalIndex = $this->selectCanonicalContactIndex($contacts, $cluster);
                $canonical = $contacts[$canonicalIndex] ?? null;
                if (!$canonical instanceof Contact) {
                    continue;
                }

                $mergedGroups++;

                foreach ($cluster as $index) {
                    if ($index === $canonicalIndex) {
                        continue;
                    }

                    $duplicate = $contacts[$index] ?? null;
                    if (!$duplicate instanceof Contact) {
                        continue;
                    }

                    $movedInteractions += $this->mergeContactInto($canonical, $duplicate);
                    $entityManager->remove($duplicate);
                    $mergedContacts++;
                }

                $entityManager->persist($canonical);
            }
        });

        return [
            'merged_contacts' => $mergedContacts,
            'merged_groups' => $mergedGroups,
            'moved_interactions' => $movedInteractions,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function addInteraction(string $contactId, array $payload): array
    {
        $this->ensureSeeded();

        $contact = $this->entityManager->getRepository(Contact::class)->find($contactId);
        if (!$contact instanceof Contact) {
            throw new NotFoundHttpException(sprintf('Contact "%s" was not found.', $contactId));
        }

        $data = $this->normalizeInteractionPayload($payload, $contactId);
        $interaction = new Interaction(
            $data['id'],
            $contact,
            $this->parseDate($data['date']) ?? new DateTimeImmutable(),
        );
        $this->applyInteractionData($interaction, $data);

        $contact->addInteraction($interaction);
        $contact->setLastContactAt($interaction->getDate());

        if ($interaction->getNextAction() !== null) {
            $contact->setNextAction($interaction->getNextAction());
        }

        if ($interaction->getNextActionAt() !== null) {
            $contact->setNextActionAt($interaction->getNextActionAt());
        }

        if ($contact->getRelationshipStatus() === ContactRelationshipStatus::Cold && $interaction->getResult() !== null && $interaction->getResult() !== '') {
            $contact->setRelationshipStatus(ContactRelationshipStatus::InProgress);
        }

        $contact->setUpdatedAt(new DateTimeImmutable());

        $this->entityManager->persist($interaction);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->decorateInteraction($interaction);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{created: int, updated: int, total: int, import_id: string}
     */
    public function importContacts(array $rows, string $sourceLabel): array
    {
        $this->ensureSeeded();

        $contacts = $this->loadContacts();
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            try {
                $payload = $this->normalizeContactPayload($row, null, $contacts, $sourceLabel);
            } catch (InvalidArgumentException) {
                continue;
            }

            $existingIndex = $this->findMatchingContactIndex($contacts, $payload);
            if ($existingIndex === null) {
                $contact = new Contact($payload['id'], $payload['display_name']);
                $this->applyContactData($contact, $payload, false);
                $this->entityManager->persist($contact);
                $contacts[] = $contact;
                $created++;
                continue;
            }

            $this->applyContactData($contacts[$existingIndex], $payload, true);
            $this->entityManager->persist($contacts[$existingIndex]);
            $updated++;
        }

        $import = new ImportLog($this->generateId('import'), $sourceLabel);
        $import->setTotal(count($rows));
        $import->setCreated($created);
        $import->setUpdated($updated);
        $import->setImportedAt(new DateTimeImmutable());
        $import->setErrors([]);

        $this->entityManager->persist($import);
        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => count($rows),
            'import_id' => $import->getId(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentInteractions(int $limit = 5): array
    {
        return array_slice($this->sortInteractions($this->decorateInteractions($this->loadInteractions())), 0, $limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listInteractionsForContact(string $contactId): array
    {
        $interactions = array_values(array_filter(
            $this->decorateInteractions($this->loadInteractions()),
            static fn (array $interaction): bool => ($interaction['contact_id'] ?? null) === $contactId,
        ));

        return $this->sortInteractions($interactions);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentImports(int $limit = 5): array
    {
        return array_slice($this->sortImports($this->decorateImports($this->loadImports())), 0, $limit);
    }

    /**
     * @return list<array{organization: string, count: int, last_contact_at: string|null}>
     */
    public function getOrganizationsSummary(int $limit = 6): array
    {
        return array_slice($this->buildOrganizations($this->decorateContacts($this->loadContacts())), 0, $limit);
    }

    /**
     * @return array<string, string>
     */
    public function getPlatformStatusOptions(): array
    {
        return PlatformStatus::labels();
    }

    /**
     * @return array<string, string>
     */
    public function getContactPriorityOptions(): array
    {
        return ContactPriority::labels();
    }

    /**
     * @return array<string, string>
     */
    public function getImportSourceOptions(): array
    {
        return ContactImportSource::labels();
    }

    /**
     * @return array<string, string>
     */
    public function getContactRelationOptions(): array
    {
        return ContactRelationshipStatus::labels();
    }

    /**
     * @return list<Platform>
     */
    private function loadPlatforms(): array
    {
        return $this->entityManager->getRepository(Platform::class)->findAll();
    }

    /**
     * @return list<Contact>
     */
    private function loadContacts(): array
    {
        return $this->entityManager->getRepository(Contact::class)->findAll();
    }

    /**
     * @return list<Interaction>
     */
    private function loadInteractions(): array
    {
        return $this->entityManager->getRepository(Interaction::class)->findAll();
    }

    /**
     * @return list<ImportLog>
     */
    private function loadImports(): array
    {
        return $this->entityManager->getRepository(ImportLog::class)->findAll();
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
     * @param list<Contact> $contacts
     *
     * @return list<array<string, mixed>>
     */
    private function decorateContacts(array $contacts): array
    {
        return array_map(fn (Contact $contact): array => $this->decorateContact($contact), $contacts);
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateContact(Contact $contact): array
    {
        return [
            'id' => $contact->getId(),
            'display_name' => $contact->getDisplayName(),
            'first_name' => $contact->getFirstName() ?? '',
            'last_name' => $contact->getLastName() ?? '',
            'organization' => $contact->getOrganization() ?? '',
            'role' => $contact->getRole() ?? '',
            'main_channel' => $contact->getMainChannel() ?? '',
            'email' => $contact->getEmail() ?? '',
            'phone' => $contact->getPhone() ?? '',
            'profile_url' => $contact->getProfileUrl() ?? '',
            'source' => $contact->getSource() ?? '',
            'priority' => $contact->getPriority()->value,
            'priority_label' => $contact->getPriorityLabel(),
            'relationship_status' => $contact->getRelationshipStatus()->value,
            'relationship_status_label' => $contact->getRelationshipStatusLabel(),
            'last_contact_at' => $this->formatDate($contact->getLastContactAt()),
            'next_action_at' => $this->formatDate($contact->getNextActionAt()),
            'next_action' => $contact->getNextAction() ?? '',
            'notes' => $contact->getNotes() ?? '',
            'tags' => array_values($contact->getTags()),
            'created_at' => $this->formatDateTime($contact->getCreatedAt()),
            'updated_at' => $this->formatDateTime($contact->getUpdatedAt()),
        ];
    }

    /**
     * @param list<Interaction> $interactions
     *
     * @return list<array<string, mixed>>
     */
    private function decorateInteractions(array $interactions): array
    {
        return array_map(fn (Interaction $interaction): array => $this->decorateInteraction($interaction), $interactions);
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateInteraction(Interaction $interaction): array
    {
        return [
            'id' => $interaction->getId(),
            'contact_id' => $interaction->getContact()?->getId() ?? '',
            'date' => $this->formatDate($interaction->getDate()),
            'channel' => $interaction->getChannel() ?? '',
            'summary' => $interaction->getSummary() ?? '',
            'result' => $interaction->getResult() ?? '',
            'next_action' => $interaction->getNextAction() ?? '',
            'next_action_at' => $this->formatDate($interaction->getNextActionAt()),
            'created_at' => $this->formatDateTime($interaction->getCreatedAt()),
        ];
    }

    /**
     * @param list<ImportLog> $imports
     *
     * @return list<array<string, mixed>>
     */
    private function decorateImports(array $imports): array
    {
        return array_map(fn (ImportLog $import): array => $this->decorateImport($import), $imports);
    }

    /**
     * @return array<string, mixed>
     */
    private function decorateImport(ImportLog $import): array
    {
        return [
            'id' => $import->getId(),
            'source_label' => $import->getSourceLabel(),
            'total' => $import->getTotal(),
            'created' => $import->getCreated(),
            'updated' => $import->getUpdated(),
            'imported_at' => $this->formatDateTime($import->getImportedAt()),
            'errors' => $import->getErrors(),
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
     * @param list<array<string, mixed>> $contacts
     *
     * @return list<array<string, mixed>>
     */
    private function sortContacts(array $contacts): array
    {
        usort($contacts, function (array $left, array $right): int {
            $priorityDiff = $this->priorityWeight($right['priority']) <=> $this->priorityWeight($left['priority']);
            if ($priorityDiff !== 0) {
                return $priorityDiff;
            }

            $leftDate = $left['last_contact_at'] ?? null;
            $rightDate = $right['last_contact_at'] ?? null;

            if ($leftDate === $rightDate) {
                return strcasecmp($left['display_name'], $right['display_name']);
            }

            if ($leftDate === null) {
                return -1;
            }

            if ($rightDate === null) {
                return 1;
            }

            return strcmp((string) $leftDate, (string) $rightDate);
        });

        return $contacts;
    }

    /**
     * @param list<array<string, mixed>> $interactions
     *
     * @return list<array<string, mixed>>
     */
    private function sortInteractions(array $interactions): array
    {
        usort($interactions, static function (array $left, array $right): int {
            return strcmp((string) ($right['date'] ?? ''), (string) ($left['date'] ?? ''));
        });

        return $interactions;
    }

    /**
     * @param list<array<string, mixed>> $imports
     *
     * @return list<array<string, mixed>>
     */
    private function sortImports(array $imports): array
    {
        usort($imports, static function (array $left, array $right): int {
            return strcmp((string) ($right['imported_at'] ?? ''), (string) ($left['imported_at'] ?? ''));
        });

        return $imports;
    }

    /**
     * @param list<array<string, mixed>> $contacts
     *
     * @return list<array{organization: string, count: int, last_contact_at: string|null}>
     */
    private function buildOrganizations(array $contacts): array
    {
        $organizations = [];

        foreach ($contacts as $contact) {
            $organization = trim((string) ($contact['organization'] ?? ''));
            if ($organization === '') {
                continue;
            }

            if (!isset($organizations[$organization])) {
                $organizations[$organization] = [
                    'organization' => $organization,
                    'count' => 0,
                    'last_contact_at' => null,
                ];
            }

            $organizations[$organization]['count']++;

            $lastContactAt = $contact['last_contact_at'] ?? null;
            if ($lastContactAt !== null && ($organizations[$organization]['last_contact_at'] === null || $lastContactAt > $organizations[$organization]['last_contact_at'])) {
                $organizations[$organization]['last_contact_at'] = $lastContactAt;
            }
        }

        $summaries = array_values($organizations);
        usort($summaries, static function (array $left, array $right): int {
            if ($left['count'] !== $right['count']) {
                return $right['count'] <=> $left['count'];
            }

            return strcasecmp($left['organization'], $right['organization']);
        });

        return $summaries;
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
     * @param array<string, mixed> $payload
     * @param list<Contact> $existingContacts
     *
     * @return array<string, mixed>
     */
    private function normalizeContactPayload(array $payload, ?string $existingId, array $existingContacts, string $sourceLabel = ''): array
    {
        $displayName = $this->normalizeString($payload['display_name'] ?? '');
        $firstName = $this->normalizeString($payload['first_name'] ?? '');
        $lastName = $this->normalizeString($payload['last_name'] ?? '');
        $organization = $this->normalizeString($payload['organization'] ?? '');
        $role = $this->normalizeString($payload['role'] ?? '');

        if ($displayName === '') {
            $displayName = trim($firstName . ' ' . $lastName);
        }

        if ($displayName === '') {
            $displayName = trim($organization . ' ' . $role);
        }

        if ($displayName === '') {
            throw new InvalidArgumentException('Contact display name is required.');
        }

        $id = $existingId ?? $this->normalizeString($payload['id'] ?? '');
        $id = $id !== '' ? $id : $this->generateId('contact');

        $contact = [
            'id' => $id,
            'display_name' => $displayName,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'organization' => $organization,
            'role' => $role,
            'main_channel' => $this->normalizeString($payload['main_channel'] ?? ''),
            'email' => $this->normalizeString($payload['email'] ?? ''),
            'phone' => $this->normalizeString($payload['phone'] ?? ''),
            'profile_url' => $this->normalizeString($payload['profile_url'] ?? ''),
            'source' => $this->normalizeString($payload['source'] ?? $sourceLabel),
            'priority' => $this->normalizeContactPriority($payload['priority'] ?? 'moyenne'),
            'relationship_status' => $this->normalizeContactRelationStatus($payload['relationship_status'] ?? 'a_relancer'),
            'last_contact_at' => $this->normalizeDate($payload['last_contact_at'] ?? null),
            'next_action_at' => $this->normalizeDate($payload['next_action_at'] ?? null),
            'next_action' => $this->normalizeString($payload['next_action'] ?? ''),
            'notes' => $this->normalizeString($payload['notes'] ?? ''),
            'tags' => $this->normalizeTags($payload['tags'] ?? []),
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $existingIndex = $this->findMatchingContactIndex($existingContacts, $contact);
        if ($existingIndex !== null) {
            $contact['created_at'] = $this->formatDateTime($existingContacts[$existingIndex]->getCreatedAt()) ?? $contact['created_at'];
        }

        return $contact;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function normalizeInteractionPayload(array $payload, string $contactId): array
    {
        $date = $this->normalizeDate($payload['date'] ?? null) ?? $this->nowDate();

        return [
            'id' => $this->generateId('interaction'),
            'contact_id' => $contactId,
            'date' => $date,
            'channel' => $this->normalizeString($payload['channel'] ?? ''),
            'summary' => $this->normalizeString($payload['summary'] ?? ''),
            'result' => $this->normalizeString($payload['result'] ?? ''),
            'next_action' => $this->normalizeString($payload['next_action'] ?? ''),
            'next_action_at' => $this->normalizeDate($payload['next_action_at'] ?? null),
            'created_at' => $this->now(),
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
     * @param array<string, mixed> $data
     */
    private function applyContactData(Contact $contact, array $data, bool $merge): void
    {
        if (!$merge) {
            $contact->setId($data['id']);
        }
        $contact->setDisplayName($merge ? $this->mergeString($contact->getDisplayName(), $data['display_name']) : $data['display_name']);
        $contact->setFirstName($merge ? $this->mergeString($contact->getFirstName(), $data['first_name']) : $data['first_name']);
        $contact->setLastName($merge ? $this->mergeString($contact->getLastName(), $data['last_name']) : $data['last_name']);
        $contact->setOrganization($merge ? $this->mergeString($contact->getOrganization(), $data['organization']) : $data['organization']);
        $contact->setRole($merge ? $this->mergeString($contact->getRole(), $data['role']) : $data['role']);
        $contact->setMainChannel($merge ? $this->mergeString($contact->getMainChannel(), $data['main_channel']) : $data['main_channel']);
        $contact->setEmail($merge ? $this->mergeString($contact->getEmail(), $data['email']) : $data['email']);
        $contact->setPhone($merge ? $this->mergeString($contact->getPhone(), $data['phone']) : $data['phone']);
        $contact->setProfileUrl($merge ? $this->mergeString($contact->getProfileUrl(), $data['profile_url']) : $data['profile_url']);
        $contact->setSource($merge ? $this->mergeString($contact->getSource(), $data['source']) : $data['source']);
        $contact->setPriority($this->contactPriorityFromValue($data['priority']));
        $contact->setRelationshipStatus($this->contactRelationFromValue($data['relationship_status']));
        $contact->setLastContactAt($merge ? $this->mergeDate($contact->getLastContactAt(), $data['last_contact_at']) : $this->parseDate($data['last_contact_at']));
        $contact->setNextActionAt($merge ? $this->mergeDate($contact->getNextActionAt(), $data['next_action_at']) : $this->parseDate($data['next_action_at']));
        $contact->setNextAction($merge ? $this->mergeString($contact->getNextAction(), $data['next_action']) : $data['next_action']);
        $contact->setNotes($merge ? $this->mergeString($contact->getNotes(), $data['notes']) : $data['notes']);
        $contact->setTags($merge ? array_values(array_unique(array_merge($contact->getTags(), $data['tags']))) : $data['tags']);

        if (!$merge) {
            $contact->setCreatedAt($this->parseDateTime($data['created_at']) ?? new DateTimeImmutable());
        }

        $contact->setUpdatedAt($this->parseDateTime($data['updated_at']) ?? new DateTimeImmutable());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function applyInteractionData(Interaction $interaction, array $data): void
    {
        $interaction->setId($data['id']);
        $interaction->setChannel($data['channel'] !== '' ? $data['channel'] : null);
        $interaction->setSummary($data['summary'] !== '' ? $data['summary'] : null);
        $interaction->setResult($data['result'] !== '' ? $data['result'] : null);
        $interaction->setNextAction($data['next_action'] !== '' ? $data['next_action'] : null);
        $interaction->setNextActionAt($this->parseDate($data['next_action_at']));
        $interaction->setCreatedAt($this->parseDateTime($data['created_at']) ?? new DateTimeImmutable());
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

    private function findContactIndex(array $contacts, string $id): ?int
    {
        foreach ($contacts as $index => $contact) {
            if ($contact instanceof Contact && $contact->getId() === $id) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function findMatchingContactIndex(array $contacts, array $candidate): ?int
    {
        $candidateEmail = mb_strtolower($this->normalizeString($candidate['email'] ?? ''));
        $candidatePhone = $this->normalizePhoneKey($candidate['phone'] ?? null);
        $candidateProfileUrl = mb_strtolower($this->normalizeString($candidate['profile_url'] ?? ''));
        $candidateName = mb_strtolower($this->normalizeString($candidate['display_name'] ?? ''));
        $candidateOrganization = mb_strtolower($this->normalizeString($candidate['organization'] ?? ''));

        foreach ($contacts as $index => $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            $contactEmail = mb_strtolower($this->normalizeString($contact->getEmail() ?? ''));
            $contactPhone = $this->normalizePhoneKey($contact->getPhone());
            $contactProfileUrl = mb_strtolower($this->normalizeString($contact->getProfileUrl() ?? ''));
            $contactName = mb_strtolower($this->normalizeString($contact->getDisplayName()));
            $contactOrganization = mb_strtolower($this->normalizeString($contact->getOrganization() ?? ''));

            if ($candidateEmail !== '' && $contactEmail !== '' && $candidateEmail === $contactEmail) {
                return $index;
            }

            if ($candidatePhone !== '' && $contactPhone !== '' && $candidatePhone === $contactPhone) {
                return $index;
            }

            if ($candidateProfileUrl !== '' && $contactProfileUrl !== '' && $candidateProfileUrl === $contactProfileUrl) {
                return $index;
            }

            if (
                $candidateName !== '' &&
                $contactName !== '' &&
                $candidateName === $contactName &&
                $candidateOrganization !== '' &&
                $candidateOrganization === $contactOrganization
            ) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param list<Contact> $contacts
     *
     * @return list<list<int>>
     */
    private function buildAutoMergeClusters(array $contacts): array
    {
        $parents = array_keys($contacts);
        $keyToIndex = [];

        foreach ($contacts as $index => $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            foreach ($this->buildContactMergeKeys($contact) as $key) {
                if ($key === '') {
                    continue;
                }

                if (array_key_exists($key, $keyToIndex)) {
                    $this->unionContactMergeRoots($parents, $index, $keyToIndex[$key]);

                    continue;
                }

                $keyToIndex[$key] = $index;
            }
        }

        $clusters = [];

        foreach ($contacts as $index => $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            $root = $this->findContactMergeRoot($parents, $index);
            $clusters[$root][] = $index;
        }

        return array_values(array_filter(
            $clusters,
            static fn (array $cluster): bool => count($cluster) > 1,
        ));
    }

    /**
     * @param list<Contact> $contacts
     * @param list<int> $cluster
     */
    private function selectCanonicalContactIndex(array $contacts, array $cluster): int
    {
        $bestIndex = $cluster[0];
        $bestScore = $this->scoreContactForMerge($contacts[$bestIndex]);
        $bestCreatedAt = $contacts[$bestIndex]->getCreatedAt()->getTimestamp();

        foreach (array_slice($cluster, 1) as $index) {
            $contact = $contacts[$index] ?? null;
            if (!$contact instanceof Contact) {
                continue;
            }

            $score = $this->scoreContactForMerge($contact);
            $createdAt = $contact->getCreatedAt()->getTimestamp();

            if ($score > $bestScore || ($score === $bestScore && $createdAt < $bestCreatedAt)) {
                $bestIndex = $index;
                $bestScore = $score;
                $bestCreatedAt = $createdAt;
            }
        }

        return $bestIndex;
    }

    private function mergeContactInto(Contact $target, Contact $source): int
    {
        $movedInteractions = 0;

        $sourceInteractions = $source->getInteractions()->toArray();
        foreach ($sourceInteractions as $interaction) {
            if (!$interaction instanceof Interaction) {
                continue;
            }

            $source->removeInteraction($interaction);
            $target->addInteraction($interaction);
            $movedInteractions++;
        }

        $target->setDisplayName($this->preferContactValue($target->getDisplayName(), $source->getDisplayName()) ?? $target->getDisplayName());
        $target->setFirstName($this->preferContactValue($target->getFirstName(), $source->getFirstName()));
        $target->setLastName($this->preferContactValue($target->getLastName(), $source->getLastName()));
        $target->setOrganization($this->preferContactValue($target->getOrganization(), $source->getOrganization()));
        $target->setRole($this->preferContactValue($target->getRole(), $source->getRole()));
        $target->setMainChannel($this->preferContactValue($target->getMainChannel(), $source->getMainChannel()));
        $target->setEmail($this->preferContactValue($target->getEmail(), $source->getEmail()));
        $target->setPhone($this->preferContactValue($target->getPhone(), $source->getPhone()));
        $target->setProfileUrl($this->preferContactValue($target->getProfileUrl(), $source->getProfileUrl()));
        $target->setSource($this->mergeSourceValues($target->getSource(), $source->getSource()));
        $target->setPriority($this->mergeContactPriority($target->getPriority(), $source->getPriority()));
        $target->setRelationshipStatus($this->mergeContactRelationshipStatus($target->getRelationshipStatus(), $source->getRelationshipStatus()));
        $target->setLastContactAt($this->mergeLatestDate($target->getLastContactAt(), $source->getLastContactAt()));
        $target->setNextActionAt($this->mergeEarliestDate($target->getNextActionAt(), $source->getNextActionAt()));
        $target->setNextAction($this->preferContactValue($target->getNextAction(), $source->getNextAction()));
        $target->setNotes($this->mergeNotes($target->getNotes(), $source));
        $target->setTags(array_values(array_unique(array_merge($target->getTags(), $source->getTags()))));
        $target->setUpdatedAt(new DateTimeImmutable());

        return $movedInteractions;
    }

    /**
     * @return list<string>
     */
    private function buildContactMergeKeys(Contact $contact): array
    {
        $keys = [];

        $phone = $this->normalizePhoneKey($contact->getPhone());
        if ($phone !== '') {
            $keys[] = 'phone:' . $phone;
        }

        $email = mb_strtolower($this->normalizeString($contact->getEmail() ?? ''));
        if ($email !== '') {
            $keys[] = 'email:' . $email;
        }

        $profileUrl = $this->normalizeProfileUrlKey($contact->getProfileUrl());
        if ($profileUrl !== '') {
            $keys[] = 'profile:' . $profileUrl;
        }

        return array_values(array_unique($keys));
    }

    /**
     * @param array<int, int> $parents
     */
    private function findContactMergeRoot(array &$parents, int $index): int
    {
        if ($parents[$index] !== $index) {
            $parents[$index] = $this->findContactMergeRoot($parents, $parents[$index]);
        }

        return $parents[$index];
    }

    /**
     * @param array<int, int> $parents
     */
    private function unionContactMergeRoots(array &$parents, int $leftIndex, int $rightIndex): void
    {
        $leftRoot = $this->findContactMergeRoot($parents, $leftIndex);
        $rightRoot = $this->findContactMergeRoot($parents, $rightIndex);

        if ($leftRoot !== $rightRoot) {
            $parents[$rightRoot] = $leftRoot;
        }
    }

    private function scoreContactForMerge(Contact $contact): int
    {
        $score = 0;
        foreach ([
            $contact->getDisplayName(),
            $contact->getFirstName(),
            $contact->getLastName(),
            $contact->getOrganization(),
            $contact->getRole(),
            $contact->getMainChannel(),
            $contact->getEmail(),
            $contact->getPhone(),
            $contact->getProfileUrl(),
            $contact->getSource(),
            $contact->getNextAction(),
            $contact->getNotes(),
        ] as $value) {
            if ($this->normalizeString((string) $value) !== '') {
                $score++;
            }
        }

        $score += count($contact->getTags());

        if ($contact->getLastContactAt() !== null) {
            $score++;
        }

        if ($contact->getNextActionAt() !== null) {
            $score++;
        }

        return $score;
    }

    private function preferContactValue(?string $current, ?string $incoming): ?string
    {
        $current = $this->normalizeString((string) $current);
        if ($current !== '') {
            return $current;
        }

        $incoming = $this->normalizeString((string) $incoming);

        return $incoming !== '' ? $incoming : null;
    }

    private function mergeSourceValues(?string $current, ?string $incoming): ?string
    {
        $labels = [];

        foreach ([$current, $incoming] as $value) {
            $value = $this->normalizeString((string) $value);
            if ($value === '') {
                continue;
            }

            foreach (preg_split('/\s*\|\s*/', $value) ?: [] as $label) {
                $label = $this->normalizeString($label);
                if ($label !== '') {
                    $labels[] = $label;
                }
            }
        }

        $labels = array_values(array_unique($labels));

        return $labels === [] ? null : implode(' | ', $labels);
    }

    private function mergeContactPriority(ContactPriority $current, ContactPriority $incoming): ContactPriority
    {
        return $this->priorityWeight($incoming->value) > $this->priorityWeight($current->value) ? $incoming : $current;
    }

    private function mergeContactRelationshipStatus(ContactRelationshipStatus $current, ContactRelationshipStatus $incoming): ContactRelationshipStatus
    {
        return $this->relationshipWeight($incoming) > $this->relationshipWeight($current) ? $incoming : $current;
    }

    private function relationshipWeight(ContactRelationshipStatus $status): int
    {
        return match ($status) {
            ContactRelationshipStatus::Priority => 5,
            ContactRelationshipStatus::FollowUp => 4,
            ContactRelationshipStatus::InProgress => 3,
            ContactRelationshipStatus::Waiting => 2,
            ContactRelationshipStatus::Cold => 1,
        };
    }

    private function mergeLatestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        if ($current === null) {
            return $incoming;
        }

        if ($incoming === null) {
            return $current;
        }

        return $incoming > $current ? $incoming : $current;
    }

    private function mergeEarliestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        if ($current === null) {
            return $incoming;
        }

        if ($incoming === null) {
            return $current;
        }

        return $incoming < $current ? $incoming : $current;
    }

    private function mergeNotes(?string $currentNotes, Contact $source): ?string
    {
        $blocks = [];
        $currentNotes = $this->normalizeString((string) $currentNotes);
        if ($currentNotes !== '') {
            $blocks[] = $currentNotes;
        }

        $snapshot = $this->buildMergeSnapshot($source);
        if ($snapshot !== '') {
            $blocks[] = $snapshot;
        }

        return $blocks === [] ? null : implode("\n\n", $blocks);
    }

    private function buildMergeSnapshot(Contact $source): string
    {
        $lines = [
            sprintf('Fusion automatique du doublon %s', $source->getId()),
        ];

        $fields = [
            'Nom affiché' => $source->getDisplayName(),
            'Prénom' => $source->getFirstName(),
            'Nom' => $source->getLastName(),
            'Entreprise' => $source->getOrganization(),
            'Rôle' => $source->getRole(),
            'Canal principal' => $source->getMainChannel(),
            'Email' => $source->getEmail(),
            'Téléphone' => $source->getPhone(),
            'Profil' => $source->getProfileUrl(),
            'Source' => $source->getSource(),
            'Priorité' => $source->getPriorityLabel(),
            'Relation' => $source->getRelationshipStatusLabel(),
            'Dernier contact' => $this->formatDate($source->getLastContactAt()),
            'Prochaine action' => $source->getNextAction(),
            'Prochaine action le' => $this->formatDate($source->getNextActionAt()),
        ];

        foreach ($fields as $label => $value) {
            $value = $this->normalizeString((string) $value);
            if ($value !== '') {
                $lines[] = sprintf('- %s: %s', $label, $value);
            }
        }

        if ($source->getTags() !== []) {
            $lines[] = sprintf('- Tags: %s', implode(', ', $source->getTags()));
        }

        $sourceNotes = $this->normalizeString((string) $source->getNotes());
        if ($sourceNotes !== '') {
            $lines[] = '- Notes d origine:';
            $lines[] = $sourceNotes;
        }

        return implode("\n", $lines);
    }

    private function normalizePhoneKey(mixed $phone): string
    {
        $phone = $this->normalizeString((string) $phone);
        if ($phone === '') {
            return '';
        }

        $phone = preg_replace('/[^0-9+]/', '', $phone) ?? $phone;
        if (str_starts_with($phone, '00')) {
            $phone = '+' . substr($phone, 2);
        }

        return mb_strtolower($phone);
    }

    private function normalizeProfileUrlKey(mixed $profileUrl): string
    {
        $profileUrl = mb_strtolower($this->normalizeString((string) $profileUrl));

        return rtrim($profileUrl, '/');
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

    private function contactPriorityFromValue(mixed $priority): ContactPriority
    {
        $priority = $this->normalizeString((string) $priority);

        return ContactPriority::tryFrom($priority) ?? ContactPriority::default();
    }

    private function contactRelationFromValue(mixed $status): ContactRelationshipStatus
    {
        $status = $this->normalizeString((string) $status);

        return ContactRelationshipStatus::tryFrom($status) ?? ContactRelationshipStatus::default();
    }

    /**
     * @param mixed $tags
     *
     * @return list<string>
     */
    private function normalizeTags(mixed $tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,\n;]/', $tags) ?: [];
        }

        if (!is_array($tags)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $tag): string => $this->normalizeString((string) $tag),
            $tags,
        ), static fn (string $tag): bool => $tag !== '')));
    }

    private function normalizePlatformStatus(mixed $status): string
    {
        return $this->platformStatusFromValue($status)->value;
    }

    private function normalizeContactPriority(mixed $priority): string
    {
        return $this->contactPriorityFromValue($priority)->value;
    }

    private function normalizeContactRelationStatus(mixed $status): string
    {
        return $this->contactRelationFromValue($status)->value;
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

    private function parseDateTime(mixed $value): ?DateTimeImmutable
    {
        $value = $this->normalizeString((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDate(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format('Y-m-d') : null;
    }

    private function formatDateTime(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format(DATE_ATOM) : null;
    }

    private function mergeString(?string $current, string $incoming): ?string
    {
        $incoming = $this->normalizeString($incoming);

        return $incoming !== '' ? $incoming : $current;
    }

    private function mergeDate(?DateTimeImmutable $current, mixed $incoming): ?DateTimeImmutable
    {
        $incoming = $this->parseDate($incoming);

        return $incoming ?? $current;
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format(DATE_ATOM);
    }

    private function nowDate(): string
    {
        return (new DateTimeImmutable())->format('Y-m-d');
    }

    private function priorityWeight(string $priority): int
    {
        return match ($priority) {
            'haute' => 3,
            'moyenne' => 2,
            'basse' => 1,
            default => 0,
        };
    }

    private function slugify(string $value): string
    {
        $value = mb_strtolower($value);
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value;
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}
