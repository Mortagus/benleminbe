<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\ImportLog;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class ContactImportService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactWritePolicyService $writePolicy,
    ) {
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array{created: int, updated: int, total: int, import_id: string}
     */
    public function importContacts(array $rows, string $sourceLabel): array
    {
        $contacts = $this->loadContacts();
        $created = 0;
        $updated = 0;

        foreach ($rows as $row) {
            try {
                $payload = $this->writePolicy->normalizeContactPayload($row, null, $contacts, $sourceLabel);
            } catch (InvalidArgumentException) {
                continue;
            }

            $existingIndex = $this->writePolicy->findMatchingContactIndex($contacts, $payload);
            if ($existingIndex === null) {
                $contact = new Contact($payload['id'], $payload['display_name']);
                $this->writePolicy->applyContactData($contact, $payload, false);
                $this->entityManager->persist($contact);
                $contacts[] = $contact;
                $created++;
                continue;
            }

            $this->writePolicy->applyContactData($contacts[$existingIndex], $payload, true);
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
     * @return list<Contact>
     */
    private function loadContacts(): array
    {
        /** @var list<Contact> $contacts */
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        return $contacts;
    }

    private function generateId(string $prefix): string
    {
        return sprintf('%s_%s', $prefix, bin2hex(random_bytes(8)));
    }
}
