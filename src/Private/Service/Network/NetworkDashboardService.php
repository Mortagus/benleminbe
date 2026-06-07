<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

final class NetworkDashboardService
{
    public function __construct(
        private readonly PlatformService $platformService,
        private readonly ContactService $contactService,
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
        $platforms = $this->platformService->listPlatforms();
        $contacts = $this->contactService->listContacts();
        $priorityContacts = $this->contactService->getPriorityContacts(8, ContactRoleClassifier::PRIORITY_DASHBOARD_CATEGORIES);
        $interactions = $this->contactService->getRecentInteractions(9999);
        $imports = $this->contactService->getRecentImports(9999);

        return [
            'stats' => [
                'platforms_total' => count($platforms),
                'platforms_configured' => count(array_filter($platforms, static fn (array $platform): bool => $platform['profile_url'] !== '')),
                'contacts_total' => count($contacts),
                'contacts_high_priority' => count(array_filter($contacts, static fn (array $contact): bool => $contact['priority'] === 'haute')),
                'contacts_to_followup' => count(array_filter($contacts, static fn (array $contact): bool => in_array($contact['relationship_status'], ['prioritaire', 'a_relancer'], true))),
            ],
            'platforms' => array_slice($platforms, 0, 6),
            'contacts' => $priorityContacts,
            'organizations' => array_slice($this->contactService->getOrganizationsSummary(), 0, 6),
            'recent_interactions' => array_slice($interactions, 0, 5),
            'recent_imports' => array_slice($imports, 0, 5),
        ];
    }
}
