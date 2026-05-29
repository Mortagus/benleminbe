<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Enum\Network\ContactImportSource;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Backward-compatible facade kept for tests and legacy callers.
 */
final class NetworkRepository
{
    public function __construct(
        private readonly PlatformService $platformService,
        private readonly ContactService $contactService,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPlatforms(string $query = ''): array
    {
        return $this->platformService->listPlatforms($query);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlatform(string $slug): array
    {
        return $this->platformService->getPlatform($slug);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function savePlatform(array $payload, ?string $existingSlug = null): array
    {
        return $this->platformService->savePlatform($payload, $existingSlug);
    }

    /**
     * @param array<string, mixed> $criteria
     *
     * @return list<array<string, mixed>>
     */
    public function listContacts(array $criteria = []): array
    {
        return $this->contactService->listContacts($criteria);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContact(string $id): array
    {
        return $this->contactService->getContact($id);
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function saveContact(array $payload, ?string $existingId = null): array
    {
        return $this->contactService->saveContact($payload, $existingId);
    }

    public function deleteContact(string $id): void
    {
        $this->contactService->deleteContact($id);
    }

    /**
     * @return array{contacts: int, interactions: int, imports: int, reviews: int}
     */
    public function resetNetworkData(): array
    {
        return $this->contactService->resetNetworkData();
    }

    /**
     * @return array{merged_contacts: int, merged_groups: int, moved_interactions: int}
     */
    public function autoMergeContacts(): array
    {
        return $this->contactService->autoMergeContacts();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function addInteraction(string $contactId, array $payload): array
    {
        return $this->contactService->addInteraction($contactId, $payload);
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{created: int, updated: int, total: int, import_id: string}
     */
    public function importContacts(array $rows, string $sourceLabel): array
    {
        return $this->contactService->importContacts($rows, $sourceLabel);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function parseImportRows(?UploadedFile $uploadedFile, string $content, ContactImportSource $source): array
    {
        return $this->contactService->parseImportRows($uploadedFile, $content, $source);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentInteractions(int $limit = 5): array
    {
        return $this->contactService->getRecentInteractions($limit);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listInteractionsForContact(string $contactId): array
    {
        return $this->contactService->listInteractionsForContact($contactId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getRecentImports(int $limit = 5): array
    {
        return $this->contactService->getRecentImports($limit);
    }

    /**
     * @return list<array{organization: string, count: int, last_contact_at: string|null}>
     */
    public function getOrganizationsSummary(int $limit = 6): array
    {
        return $this->contactService->getOrganizationsSummary($limit);
    }

    /**
     * @return array<string, string>
     */
    public function getPlatformStatusOptions(): array
    {
        return $this->platformService->getStatusOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getContactPriorityOptions(): array
    {
        return $this->contactService->getPriorityOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getImportSourceOptions(): array
    {
        return $this->contactService->getImportSourceOptions();
    }

    /**
     * @return array<string, string>
     */
    public function getContactRelationOptions(): array
    {
        return $this->contactService->getRelationOptions();
    }
}
