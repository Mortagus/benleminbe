<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ImportLog;
use App\Entity\Network\Interaction;
use App\Enum\Network\ContactImportSource;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContactService
{
    private const string CONTACT_SORT_DEFAULT = 'default';
    private const string CONTACT_SORT_ORGANIZATION = 'organization';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactMergeRulesService $mergeRules,
        private readonly ContactRoleClassifier $roleClassifier,
        private readonly ContactAutoMergeService $autoMergeService,
        private readonly ContactWritePolicyService $writePolicy,
        private readonly ContactImportService $contactImportService,
        private readonly ContactImportParser $contactImportParser,
    ) {
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{
     *     contacts: list<array<string, mixed>>,
     *     currentQuery: string,
     *     currentPriority: string,
     *     currentRelationStatus: string,
     *     currentOrganizationState: string,
     *     currentRoleState: string,
     *     currentRole: string,
     *     currentRoleCategory: string,
     *     currentLetter: string,
     *     currentSort: string,
     *     currentPage: int,
     *     totalPages: int,
     *     visibleFrom: int,
     *     visibleTo: int,
     *     totalContacts: int,
     *     hasPrevious: bool,
     *     previousPage: int,
     *     hasNext: bool,
     *     nextPage: int,
     *     paginationPages: list<array{type: string, page?: int, label?: string, current?: bool}>,
     *     letterOptions: list<array{value: string, label: string, active: bool}>,
     *     roleStateOptions: array<string, string>,
     *     roleOptions: array<string, string>,
     *     roleCategoryOptions: array<string, string>,
     *     sortOptions: array<string, string>,
     *     organizationStateOptions: array<string, string>,
     *     priorityOptions: array<string, string>,
     *     relationOptions: array<string, string>
     * }
     */
    public function getContactsPage(array $filters, int $page, int $pageSize): array
    {
        $contacts = $this->listContacts($filters);
        $totalContacts = count($contacts);
        $pageSize = max(1, $pageSize);
        $totalPages = max(1, (int) ceil($totalContacts / $pageSize));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $pageSize;
        $pageContacts = array_slice($contacts, $offset, $pageSize);
        $visibleFrom = $pageContacts === [] ? 0 : $offset + 1;
        $visibleTo = $pageContacts === [] ? 0 : min($totalContacts, $offset + count($pageContacts));
        $currentLetter = $this->normalizeLetterFilter($filters['letter'] ?? '');
        $currentOrganizationState = $this->normalizeOrganizationStateFilter($filters['organization_state'] ?? '');
        $currentRoleState = $this->normalizeRoleStateFilter($filters['role_state'] ?? '');
        $currentRole = $this->normalizeRoleFilter($filters['role'] ?? '');
        $currentRoleCategory = $this->normalizeRoleCategoryFilter($filters['role_category'] ?? '');
        $currentSort = $this->normalizeSortFilter($filters['sort'] ?? '');

        return [
            'contacts' => $pageContacts,
            'currentQuery' => $this->normalizeString($filters['search'] ?? ''),
            'currentPriority' => $this->normalizeString($filters['priority'] ?? ''),
            'currentRelationStatus' => $this->normalizeString($filters['relationship_status'] ?? ''),
            'currentOrganizationState' => $currentOrganizationState,
            'currentRoleState' => $currentRoleState,
            'currentRole' => $currentRole,
            'currentRoleCategory' => $currentRoleCategory,
            'currentLetter' => $currentLetter,
            'currentSort' => $currentSort,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'visibleFrom' => $visibleFrom,
            'visibleTo' => $visibleTo,
            'totalContacts' => $totalContacts,
            'hasPrevious' => $page > 1,
            'previousPage' => max(1, $page - 1),
            'hasNext' => $page < $totalPages,
            'nextPage' => min($totalPages, $page + 1),
            'paginationPages' => $this->buildPaginationPages($page, $totalPages),
            'letterOptions' => $this->buildLetterOptions($currentLetter),
            'roleStateOptions' => $this->getRoleStateOptions(),
            'roleOptions' => $this->getRoleOptions(),
            'roleCategoryOptions' => $this->getRoleCategoryOptions(),
            'sortOptions' => $this->getSortOptions(),
            'organizationStateOptions' => $this->getOrganizationStateOptions(),
            'priorityOptions' => $this->getPriorityOptions(),
            'relationOptions' => $this->getRelationOptions(),
        ];
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
        $organizationState = $this->normalizeOrganizationStateFilter($criteria['organization_state'] ?? '');
        $roleState = $this->normalizeRoleStateFilter($criteria['role_state'] ?? '');
        $role = $this->normalizeRoleFilter($criteria['role'] ?? '');
        $roleCategory = $this->normalizeRoleCategoryFilter($criteria['role_category'] ?? '');
        $letter = $this->normalizeLetterFilter($criteria['letter'] ?? '');
        $sort = $this->normalizeSortFilter($criteria['sort'] ?? '');

        $contacts = array_values(array_filter($contacts, function (array $contact) use ($search, $priority, $status, $organizationState, $roleState, $role, $roleCategory, $letter): bool {
            if ($priority !== '' && $contact['priority'] !== $priority) {
                return false;
            }

            if ($status !== '' && $contact['relationship_status'] !== $status) {
                return false;
            }

            if ($organizationState === 'with' && !$this->contactHasOrganization($contact)) {
                return false;
            }

            if ($organizationState === 'without' && $this->contactHasOrganization($contact)) {
                return false;
            }

            if ($roleState === 'with' && !$this->contactHasRole($contact)) {
                return false;
            }

            if ($roleState === 'without' && $this->contactHasRole($contact)) {
                return false;
            }

            if ($role !== '' && $this->normalizeRoleKey($contact['role'] ?? '') !== $role) {
                return false;
            }

            if ($roleCategory !== '' && ($contact['role_category'] ?? '') !== $roleCategory) {
                return false;
            }

            if ($letter !== '' && $this->contactInitialLetter((string) ($contact['display_name'] ?? '')) !== $letter) {
                return false;
            }

            if ($search === '') {
                return true;
            }

            $haystack = implode(' ', array_filter([
                $contact['display_name'],
                $contact['organization'],
                $contact['role'],
                is_array($contact['email'] ?? null) ? implode(' ', $contact['email']) : ($contact['email'] ?? ''),
                is_array($contact['phone'] ?? null) ? implode(' ', $contact['phone']) : ($contact['phone'] ?? ''),
                $contact['profile_url'],
                $contact['notes'],
                implode(' ', $contact['tags']),
            ]));

            return str_contains(mb_strtolower($haystack), $search);
        }));

        return $this->sortContacts($contacts, $sort);
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
        $contacts = $this->loadContacts();
        $data = $this->writePolicy->normalizeContactPayload($payload, $existingId, $contacts);
        $contactIndex = $existingId !== null
            ? $this->findContactIndex($contacts, $existingId)
            : $this->writePolicy->findMatchingContactIndex($contacts, $data);

        $contact = $contactIndex !== null ? $contacts[$contactIndex] : null;

        if (!$contact instanceof Contact) {
            $contact = new Contact($data['id'], $data['display_name']);
        }

        $this->writePolicy->applyContactData($contact, $data, $contactIndex !== null);
        $this->entityManager->persist($contact);
        $this->entityManager->flush();

        return $this->decorateContact($contact);
    }

    public function deleteContact(string $id): void
    {
        $contact = $this->entityManager->getRepository(Contact::class)->find($id);
        if (!$contact instanceof Contact) {
            throw new NotFoundHttpException(sprintf('Contact "%s" was not found.', $id));
        }

        $this->entityManager->remove($contact);
        $this->entityManager->flush();
    }

    /**
     * @return array{contacts: int, interactions: int, imports: int, reviews: int}
     */
    public function resetNetworkData(): array
    {
        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $entityManager): array {
            $connection = $entityManager->getConnection();

            $deletedInteractions = (int) $connection->executeStatement('DELETE FROM network_interactions');
            $deletedReviews = (int) $connection->executeStatement('DELETE FROM network_contact_merge_reviews');
            $deletedImports = (int) $connection->executeStatement('DELETE FROM network_import_logs');
            $deletedContacts = (int) $connection->executeStatement('DELETE FROM network_contacts');

            $entityManager->clear();

            return [
                'contacts' => $deletedContacts,
                'interactions' => $deletedInteractions,
                'imports' => $deletedImports,
                'reviews' => $deletedReviews,
            ];
        });
    }

    /**
     * @return array{merged_contacts: int, merged_groups: int, moved_interactions: int}
     */
    public function autoMergeContacts(): array
    {
        return $this->autoMergeService->autoMergeContacts();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function addInteraction(string $contactId, array $payload): array
    {
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
        return $this->contactImportService->importContacts($rows, $sourceLabel);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseImportRows(?UploadedFile $uploadedFile, string $content, ContactImportSource $source): array
    {
        if ($uploadedFile instanceof UploadedFile) {
            return $this->contactImportParser->parseUploadedFile($uploadedFile, $source);
        }

        return $this->contactImportParser->parseContent($content, $source);
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
    public function getPriorityOptions(): array
    {
        return ContactPriority::labels();
    }

    /**
     * @return array<string, string>
     */
    public function getRelationOptions(): array
    {
        return ContactRelationshipStatus::labels();
    }

    /**
     * @return array<string, string>
     */
    public function getOrganizationStateOptions(): array
    {
        return [
            '' => 'Toutes',
            'with' => 'Avec entreprise',
            'without' => 'Sans entreprise',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getRoleStateOptions(): array
    {
        return [
            '' => 'Tous',
            'with' => 'Avec rôle',
            'without' => 'Sans rôle',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getRoleOptions(): array
    {
        $roles = [];

        foreach ($this->decorateContacts($this->loadContacts()) as $contact) {
            $role = $this->normalizeRoleLabel($contact['role'] ?? '');
            if ($role === null) {
                continue;
            }

            $roleKey = $this->normalizeRoleKey($role);
            if ($roleKey === '') {
                continue;
            }

            $roles[$roleKey] = $role;
        }

        asort($roles, SORT_NATURAL | SORT_FLAG_CASE);

        return ['' => 'Tous les rôles'] + $roles;
    }

    /**
     * @return array<string, string>
     */
    public function getRoleCategoryOptions(): array
    {
        return $this->roleClassifier->getCategoryOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getSortOptions(): array
    {
        return [
            self::CONTACT_SORT_DEFAULT => 'Par défaut',
            self::CONTACT_SORT_ORGANIZATION => 'Entreprise',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getImportSourceOptions(): array
    {
        return ContactImportSource::labels();
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultValues(): array
    {
        return [
            'display_name' => '',
            'first_name' => '',
            'last_name' => '',
            'organization' => '',
            'role' => '',
            'main_channel' => '',
            'email' => '',
            'phone' => '',
            'profile_url' => '',
            'source' => '',
            'priority' => 'moyenne',
            'relationship_status' => 'a_relancer',
            'last_contact_at' => '',
            'next_action_at' => '',
            'next_action' => '',
            'notes' => '',
            'tags' => '',
        ];
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
        $roleClassification = $this->roleClassifier->classify($contact->getRole());

        return [
            'id' => $contact->getId(),
            'display_name' => $contact->getDisplayName(),
            'first_name' => $contact->getFirstName() ?? '',
            'last_name' => $contact->getLastName() ?? '',
            'organization' => $contact->getOrganization() ?? '',
            'role' => $contact->getRole() ?? '',
            'role_category' => $roleClassification['category'],
            'role_category_label' => $roleClassification['label'],
            'role_category_confidence' => $roleClassification['confidence'],
            'role_category_rule' => $roleClassification['matched_rule'] ?? '',
            'main_channel' => $contact->getMainChannel() ?? '',
            'email' => implode(', ', $contact->getEmails()),
            'phone' => $this->mergeRules->formatPhoneListDisplay($contact->getPhones()),
            'emails' => $contact->getEmails(),
            'phones' => $contact->getPhones(),
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
     * @param list<array<string, mixed>> $contacts
     *
     * @return list<array<string, mixed>>
     */
    private function sortContacts(array $contacts, string $sort): array
    {
        usort($contacts, function (array $left, array $right) use ($sort): int {
            if ($sort === self::CONTACT_SORT_ORGANIZATION) {
                $leftHasOrganization = $this->contactHasOrganization($left);
                $rightHasOrganization = $this->contactHasOrganization($right);

                if ($leftHasOrganization !== $rightHasOrganization) {
                    return $leftHasOrganization ? -1 : 1;
                }

                if ($leftHasOrganization && $rightHasOrganization) {
                    $organizationDiff = strcasecmp(
                        $this->normalizeOrganizationSortKey($left['organization'] ?? ''),
                        $this->normalizeOrganizationSortKey($right['organization'] ?? ''),
                    );

                    if ($organizationDiff !== 0) {
                        return $organizationDiff;
                    }
                }
            }

            return $this->compareContactsByDefaultOrder($left, $right);
        });

        return $contacts;
    }

    /**
     * @param array<string, mixed> $left
     * @param array<string, mixed> $right
     */
    private function compareContactsByDefaultOrder(array $left, array $right): int
    {
        $priorityDiff = $this->mergeRules->priorityWeight($right['priority']) <=> $this->mergeRules->priorityWeight($left['priority']);
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
    }

    private function normalizeSortFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);
        if ($value === '') {
            return self::CONTACT_SORT_DEFAULT;
        }

        return in_array($value, [self::CONTACT_SORT_DEFAULT, self::CONTACT_SORT_ORGANIZATION], true) ? $value : self::CONTACT_SORT_DEFAULT;
    }

    private function normalizeOrganizationSortKey(mixed $value): string
    {
        return $this->mergeRules->normalizeComparableText($value);
    }

    /**
     * @param list<array<string, mixed>> $contacts
     *
     * @return list<array<string, mixed>>
     */
    private function sortContactsByDefault(array $contacts): array
    {
        usort($contacts, fn (array $left, array $right): int => $this->compareContactsByDefaultOrder($left, $right));

        return $contacts;
    }

    /**
     * @param list<array<string, mixed>> $contacts
     *
     * @return list<array<string, mixed>>
     */
    private function sortContactsByOrganization(array $contacts): array
    {
        usort($contacts, function (array $left, array $right): int {
            $leftHasOrganization = $this->contactHasOrganization($left);
            $rightHasOrganization = $this->contactHasOrganization($right);

            if ($leftHasOrganization !== $rightHasOrganization) {
                return $leftHasOrganization ? -1 : 1;
            }

            if ($leftHasOrganization && $rightHasOrganization) {
                $organizationDiff = strcasecmp(
                    $this->normalizeOrganizationSortKey($left['organization'] ?? ''),
                    $this->normalizeOrganizationSortKey($right['organization'] ?? ''),
                );

                if ($organizationDiff !== 0) {
                    return $organizationDiff;
                }
            }

            return $this->compareContactsByDefaultOrder($left, $right);
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
            $organization = $this->normalizeOrganizationLabel($contact['organization'] ?? '');
            if ($organization === null) {
                continue;
            }

            $organizationKey = $this->normalizeOrganizationSortKey($organization);
            if ($organizationKey === '') {
                continue;
            }

            if (!isset($organizations[$organizationKey])) {
                $organizations[$organizationKey] = [
                    'organization' => $organization,
                    'count' => 0,
                    'last_contact_at' => null,
                ];
            }

            $organizations[$organizationKey]['count']++;

            $lastContactAt = $contact['last_contact_at'] ?? null;
            if ($lastContactAt !== null && ($organizations[$organizationKey]['last_contact_at'] === null || $lastContactAt > $organizations[$organizationKey]['last_contact_at'])) {
                $organizations[$organizationKey]['last_contact_at'] = $lastContactAt;
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

    private function findContactIndex(array $contacts, string $id): ?int
    {
        foreach ($contacts as $index => $contact) {
            if ($contact instanceof Contact && $contact->getId() === $id) {
                return $index;
            }
        }

        return null;
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

    private function now(): string
    {
        return (new DateTimeImmutable())->format(DATE_ATOM);
    }

    private function normalizeString(mixed $value): string
    {
        return trim((string) $value);
    }

    /**
     * @return list<array{type: string, page?: int, label?: string, current?: bool}>
     */
    private function buildPaginationPages(int $currentPage, int $totalPages): array
    {
        if ($totalPages <= 1) {
            return [];
        }

        if ($totalPages <= 7) {
            $pages = [];
            for ($page = 1; $page <= $totalPages; ++$page) {
                $pages[] = $this->buildPaginationPage($page, $currentPage);
            }

            return $pages;
        }

        $start = max(2, $currentPage - 2);
        $end = min($totalPages - 1, $currentPage + 2);

        if ($currentPage <= 3) {
            $start = 2;
            $end = min($totalPages - 1, 5);
        }

        if ($currentPage >= $totalPages - 2) {
            $start = max(2, $totalPages - 4);
            $end = $totalPages - 1;
        }

        $pages = [$this->buildPaginationPage(1, $currentPage)];

        if ($start > 2) {
            $pages[] = ['type' => 'ellipsis'];
        }

        for ($page = $start; $page <= $end; ++$page) {
            $pages[] = $this->buildPaginationPage($page, $currentPage);
        }

        if ($end < $totalPages - 1) {
            $pages[] = ['type' => 'ellipsis'];
        }

        $pages[] = $this->buildPaginationPage($totalPages, $currentPage);

        return $pages;
    }

    /**
     * @return array{type: string, page: int, label: string, current: bool}
     */
    private function buildPaginationPage(int $page, int $currentPage): array
    {
        return [
            'type' => 'page',
            'page' => $page,
            'label' => (string) $page,
            'current' => $page === $currentPage,
        ];
    }

    /**
     * @return list<array{value: string, label: string, active: bool}>
     */
    private function buildLetterOptions(string $currentLetter): array
    {
        $options = [
            [
                'value' => '',
                'label' => 'Toutes',
                'active' => $currentLetter === '',
            ],
        ];

        foreach (range('A', 'Z') as $letter) {
            $options[] = [
                'value' => $letter,
                'label' => $letter,
                'active' => $currentLetter === $letter,
            ];
        }

        return $options;
    }

    private function normalizeLetterFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);
        if ($value === '') {
            return '';
        }

        $value = mb_substr($value, 0, 1, 'UTF-8');
        $value = $this->transliterateToAscii($value);
        $value = mb_strtoupper(mb_substr($value, 0, 1, 'UTF-8'), 'UTF-8');

        return preg_match('/^[A-Z]$/', $value) === 1 ? $value : '';
    }

    private function normalizeOrganizationStateFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);

        return in_array($value, ['with', 'without'], true) ? $value : '';
    }

    private function normalizeRoleStateFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);

        return in_array($value, ['with', 'without'], true) ? $value : '';
    }

    private function normalizeRoleFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);

        return $value !== '' ? $this->normalizeRoleKey($value) : '';
    }

    private function normalizeRoleCategoryFilter(mixed $value): string
    {
        $value = $this->normalizeString($value);

        return $value !== '' && $this->roleClassifier->isKnownCategory($value) ? $value : '';
    }

    private function normalizeRoleKey(mixed $value): string
    {
        return $this->mergeRules->normalizeComparableText($value);
    }

    private function normalizeRoleLabel(mixed $value): ?string
    {
        $value = $this->normalizeString($value);

        return $value !== '' ? $value : null;
    }

    private function normalizeOrganizationLabel(mixed $value): ?string
    {
        $value = $this->mergeRules->normalizeOrganizationName($value);
        if ($value !== null && $value !== '') {
            return $value;
        }

        $value = $this->normalizeString($value);

        return $value !== '' ? $value : null;
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function contactHasOrganization(array $contact): bool
    {
        return trim((string) ($contact['organization'] ?? '')) !== '';
    }

    /**
     * @param array<string, mixed> $contact
     */
    private function contactHasRole(array $contact): bool
    {
        return trim((string) ($contact['role'] ?? '')) !== '';
    }

    private function contactInitialLetter(string $displayName): string
    {
        return $this->normalizeLetterFilter($displayName);
    }

    private function transliterateToAscii(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted === false || $converted === '' ? $value : $converted;
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}
