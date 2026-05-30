<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use Doctrine\ORM\EntityManagerInterface;

final class ContactStatisticsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return array{
     *     total_contacts: int,
     *     contacts_with_organization: int,
     *     contacts_with_linkedin: int,
     *     contacts_with_email: int,
     *     contacts_with_phone: int,
     *     contacts_to_qualify: int
     * }
     */
    public function getContactOverviewStats(): array
    {
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        $stats = [
            'total_contacts' => 0,
            'contacts_with_organization' => 0,
            'contacts_with_linkedin' => 0,
            'contacts_with_email' => 0,
            'contacts_with_phone' => 0,
            'contacts_to_qualify' => 0,
        ];

        foreach ($contacts as $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            $stats['total_contacts']++;

            if ($this->hasOrganization($contact)) {
                $stats['contacts_with_organization']++;
            }

            if ($this->mergeRules->isLinkedInContact($contact)) {
                $stats['contacts_with_linkedin']++;
            }

            if ($contact->getEmails() !== []) {
                $stats['contacts_with_email']++;
            }

            if ($contact->getPhones() !== []) {
                $stats['contacts_with_phone']++;
            }

            if ($this->mergeRules->isSparseContactForAutoMerge($contact)) {
                $stats['contacts_to_qualify']++;
            }
        }

        return $stats;
    }

    private function hasOrganization(Contact $contact): bool
    {
        return trim((string) $contact->getOrganization()) !== '';
    }
}
