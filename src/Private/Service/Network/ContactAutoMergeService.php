<?php

declare(strict_types=1);

namespace App\Private\Service\Network;

use App\Entity\Network\Contact;
use App\Entity\Network\Interaction;
use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

final class ContactAutoMergeService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ContactMergeRulesService $mergeRules,
    ) {
    }

    /**
     * @return array{merged_contacts: int, merged_groups: int, moved_interactions: int}
     */
    public function autoMergeContacts(): array
    {
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

                    if (!$this->canAutoMergeContacts($canonical, $duplicate)) {
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
     * @return list<Contact>
     */
    private function loadContacts(): array
    {
        /** @var list<Contact> $contacts */
        $contacts = $this->entityManager->getRepository(Contact::class)->findAll();

        return $contacts;
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

        $sparseIndices = [];
        foreach ($contacts as $index => $contact) {
            if (!$contact instanceof Contact) {
                continue;
            }

            if ($this->mergeRules->isSparseContactForAutoMerge($contact)) {
                $sparseIndices[] = $index;
            }
        }

        $sparseCount = count($sparseIndices);
        for ($leftOffset = 0; $leftOffset < $sparseCount; ++$leftOffset) {
            $leftIndex = $sparseIndices[$leftOffset];
            $left = $contacts[$leftIndex] ?? null;
            if (!$left instanceof Contact) {
                continue;
            }

            for ($rightOffset = $leftOffset + 1; $rightOffset < $sparseCount; ++$rightOffset) {
                $rightIndex = $sparseIndices[$rightOffset];
                $right = $contacts[$rightIndex] ?? null;
                if (!$right instanceof Contact) {
                    continue;
                }

                if ($this->canAutoMergeSparseDisplayNameContacts($left, $right)) {
                    $this->unionContactMergeRoots($parents, $leftIndex, $rightIndex);
                }
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
        $target->setOrganization($this->mergeOrganizationValue($target->getOrganization(), $source->getOrganization()));
        $target->setRole($this->preferContactValue($target->getRole(), $source->getRole()));
        $target->setMainChannel($this->resolveMainChannelValue(
            $target->getMainChannel(),
            $source->getMainChannel(),
            $target->getProfileUrl(),
            $source->getProfileUrl(),
        ));
        $target->setEmail($this->mergeRules->mergeEmailLists($target->getEmails(), $source->getEmails()));
        $target->setPhone($this->mergeRules->mergePhoneLists($target->getPhones(), $source->getPhones()));
        $target->setProfileUrl($this->preferContactValue($target->getProfileUrl(), $source->getProfileUrl()));
        $target->setSource($this->mergeSourceValues($target->getSource(), $source->getSource()));
        $target->setPriority($this->mergeContactPriority($target->getPriority(), $source->getPriority()));
        $target->setRelationshipStatus($this->mergeContactRelationshipStatus($target->getRelationshipStatus(), $source->getRelationshipStatus()));
        $target->setLastContactAt($this->mergeLatestDate($target->getLastContactAt(), $source->getLastContactAt()));
        $target->setNextActionAt($this->mergeEarliestDate($target->getNextActionAt(), $source->getNextActionAt()));
        $target->setNextAction($this->preferContactValue($target->getNextAction(), $source->getNextAction()));
        $target->setNotes($this->mergeNotes($target->getNotes(), $source));
        $target->setTags($this->mergeRules->mergeTags($target->getTags(), $source->getTags()));
        $target->setUpdatedAt(new DateTimeImmutable());

        return $movedInteractions;
    }

    private function canAutoMergeContacts(Contact $left, Contact $right): bool
    {
        if ($this->mergeRules->hasSharedPhoneValue($left->getPhones(), $right->getPhones())) {
            return true;
        }

        if ($this->mergeRules->normalizeProfileUrlKey($left->getProfileUrl()) !== '' && $this->mergeRules->normalizeProfileUrlKey($left->getProfileUrl()) === $this->mergeRules->normalizeProfileUrlKey($right->getProfileUrl())) {
            return true;
        }

        if ($this->mergeRules->hasSharedEmailValue($left->getEmails(), $right->getEmails())) {
            return true;
        }

        if ($this->canAutoMergeSparseDisplayNameContacts($left, $right)) {
            return true;
        }

        foreach ([
            ['left' => $left->getDisplayName(), 'right' => $right->getDisplayName()],
            ['left' => $left->getFirstName(), 'right' => $right->getFirstName()],
            ['left' => $left->getLastName(), 'right' => $right->getLastName()],
            ['left' => $left->getOrganization(), 'right' => $right->getOrganization()],
            ['left' => $left->getRole(), 'right' => $right->getRole()],
            ['left' => $left->getProfileUrl(), 'right' => $right->getProfileUrl()],
            ['left' => $left->getNextAction(), 'right' => $right->getNextAction()],
            ['left' => $left->getNotes(), 'right' => $right->getNotes()],
            ['left' => $left->getPriority()->value, 'right' => $right->getPriority()->value],
            ['left' => $left->getRelationshipStatus()->value, 'right' => $right->getRelationshipStatus()->value],
            ['left' => $this->formatDate($left->getLastContactAt()) ?? '', 'right' => $this->formatDate($right->getLastContactAt()) ?? ''],
            ['left' => $this->formatDate($left->getNextActionAt()) ?? '', 'right' => $this->formatDate($right->getNextActionAt()) ?? ''],
        ] as $pair) {
            $leftValue = $this->mergeRules->normalizeComparableText($pair['left']);
            $rightValue = $this->mergeRules->normalizeComparableText($pair['right']);

            if ($leftValue !== '' && $rightValue !== '' && $leftValue !== $rightValue) {
                return false;
            }
        }

        $leftMainChannel = $this->mergeRules->normalizeComparableText($left->getMainChannel());
        $rightMainChannel = $this->mergeRules->normalizeComparableText($right->getMainChannel());
        if ($leftMainChannel !== '' && $rightMainChannel !== '' && $leftMainChannel !== $rightMainChannel) {
            if (!$this->mergeRules->isLinkedInProfileUrl($left->getProfileUrl()) && !$this->mergeRules->isLinkedInProfileUrl($right->getProfileUrl())) {
                return false;
            }
        }

        return true;
    }

    private function canAutoMergeSparseDisplayNameContacts(Contact $left, Contact $right): bool
    {
        if ($this->mergeRules->isSparseContactForAutoMerge($left) === false || $this->mergeRules->isSparseContactForAutoMerge($right) === false) {
            return false;
        }

        $leftDisplayName = $this->mergeRules->normalizeComparableText($left->getDisplayName());
        $rightDisplayName = $this->mergeRules->normalizeComparableText($right->getDisplayName());
        if ($leftDisplayName === '' || $rightDisplayName === '') {
            return false;
        }

        if (levenshtein($leftDisplayName, $rightDisplayName) !== 1) {
            return false;
        }

        foreach ([
            ['left' => $left->getFirstName(), 'right' => $right->getFirstName()],
            ['left' => $left->getLastName(), 'right' => $right->getLastName()],
            ['left' => $left->getOrganization(), 'right' => $right->getOrganization()],
            ['left' => $left->getRole(), 'right' => $right->getRole()],
            ['left' => $left->getProfileUrl(), 'right' => $right->getProfileUrl()],
            ['left' => $left->getNextAction(), 'right' => $right->getNextAction()],
            ['left' => $left->getNotes(), 'right' => $right->getNotes()],
            ['left' => $left->getPriority()->value, 'right' => $right->getPriority()->value],
            ['left' => $left->getRelationshipStatus()->value, 'right' => $right->getRelationshipStatus()->value],
            ['left' => $this->formatDate($left->getLastContactAt()) ?? '', 'right' => $this->formatDate($right->getLastContactAt()) ?? ''],
            ['left' => $this->formatDate($left->getNextActionAt()) ?? '', 'right' => $this->formatDate($right->getNextActionAt()) ?? ''],
        ] as $pair) {
            $leftValue = $this->mergeRules->normalizeComparableText($pair['left']);
            $rightValue = $this->mergeRules->normalizeComparableText($pair['right']);

            if ($leftValue !== '' && $rightValue !== '' && $leftValue !== $rightValue) {
                return false;
            }
        }

        $leftMainChannel = $this->mergeRules->normalizeComparableText($left->getMainChannel());
        $rightMainChannel = $this->mergeRules->normalizeComparableText($right->getMainChannel());
        if ($leftMainChannel !== '' && $rightMainChannel !== '' && $leftMainChannel !== $rightMainChannel) {
            if (!$this->mergeRules->isLinkedInProfileUrl($left->getProfileUrl()) && !$this->mergeRules->isLinkedInProfileUrl($right->getProfileUrl())) {
                return false;
            }
        }

        return true;
    }

    private function buildContactMergeKeys(Contact $contact): array
    {
        $keys = [];

        foreach ($contact->getPhones() as $phone) {
            $phoneKey = $this->mergeRules->normalizePhoneKey($phone);
            if ($phoneKey !== '') {
                $keys[] = 'phone:' . $phoneKey;
            }
        }

        foreach ($contact->getEmails() as $email) {
            $emailKey = mb_strtolower($this->mergeRules->normalizeOptionalString($email) ?? '');
            if ($emailKey !== '') {
                $keys[] = 'email:' . $emailKey;
            }
        }

        $profileUrl = $this->mergeRules->normalizeProfileUrlKey($contact->getProfileUrl());
        if ($profileUrl !== '') {
            $keys[] = 'profile:' . $profileUrl;
        }

        $identityKey = $this->buildContactIdentityKey($contact);
        if ($identityKey !== null) {
            $keys[] = 'identity:' . $identityKey;
        }

        return array_values(array_unique($keys));
    }

    private function buildContactIdentityKey(Contact $contact): ?string
    {
        $displayName = $this->mergeRules->normalizeComparableText($contact->getDisplayName());
        $firstName = $this->mergeRules->normalizeComparableText($contact->getFirstName());
        $lastName = $this->mergeRules->normalizeComparableText($contact->getLastName());

        if ($displayName === '' || $firstName === '' || $lastName === '') {
            return null;
        }

        return $displayName . '|' . $firstName . '|' . $lastName;
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
        return $this->mergeRules->scoreContactCompleteness($contact);
    }

    private function preferContactValue(?string $current, ?string $incoming): ?string
    {
        return $this->mergeRules->preferContactValue($current, $incoming);
    }

    private function mergeSourceValues(?string $current, ?string $incoming): ?string
    {
        return $this->mergeRules->mergeSourceValues($current, $incoming);
    }

    private function mergeContactPriority(ContactPriority $current, ContactPriority $incoming): ContactPriority
    {
        return $this->mergeRules->mergeContactPriority($current, $incoming);
    }

    private function mergeContactRelationshipStatus(ContactRelationshipStatus $current, ContactRelationshipStatus $incoming): ContactRelationshipStatus
    {
        return $this->mergeRules->mergeContactRelationshipStatus($current, $incoming);
    }

    private function mergeLatestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        return $this->mergeRules->mergeLatestDate($current, $incoming);
    }

    private function mergeEarliestDate(?DateTimeImmutable $current, ?DateTimeImmutable $incoming): ?DateTimeImmutable
    {
        return $this->mergeRules->mergeEarliestDate($current, $incoming);
    }

    private function mergeOrganizationValue(?string $current, ?string $incoming): ?string
    {
        $incoming = $this->mergeRules->normalizeOrganizationName($incoming);
        if ($incoming !== null && $incoming !== '') {
            return $incoming;
        }

        $current = $this->mergeRules->normalizeOrganizationName($current);

        return $current !== null && $current !== '' ? $current : null;
    }

    private function resolveMainChannelValue(mixed $currentMainChannel, mixed $incomingMainChannel, mixed $currentProfileUrl, mixed $incomingProfileUrl): ?string
    {
        if ($this->mergeRules->isLinkedInProfileUrl($currentProfileUrl) || $this->mergeRules->isLinkedInProfileUrl($incomingProfileUrl)) {
            return 'LinkedIn';
        }

        $incoming = $this->mergeRules->normalizeOptionalString($incomingMainChannel);
        if ($incoming !== null) {
            return $incoming;
        }

        $current = $this->mergeRules->normalizeOptionalString($currentMainChannel);

        return $current;
    }

    private function mergeNotes(?string $currentNotes, Contact $source): ?string
    {
        $blocks = [];
        $currentNotes = $this->mergeRules->normalizeOptionalString($currentNotes);
        if ($currentNotes !== null) {
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
            'Email' => implode(', ', $source->getEmails()),
            'Téléphone' => implode(', ', $source->getPhones()),
            'Profil' => $source->getProfileUrl(),
            'Source' => $source->getSource(),
            'Priorité' => $source->getPriorityLabel(),
            'Relation' => $source->getRelationshipStatusLabel(),
            'Dernier contact' => $this->formatDate($source->getLastContactAt()),
            'Prochaine action' => $source->getNextAction(),
            'Prochaine action le' => $this->formatDate($source->getNextActionAt()),
        ];

        foreach ($fields as $label => $value) {
            $value = $this->mergeRules->normalizeOptionalString($value);
            if ($value !== null) {
                $lines[] = sprintf('- %s: %s', $label, $value);
            }
        }

        if ($source->getTags() !== []) {
            $lines[] = sprintf('- Tags: %s', implode(', ', $source->getTags()));
        }

        $sourceNotes = $this->mergeRules->normalizeOptionalString($source->getNotes());
        if ($sourceNotes !== null) {
            $lines[] = '- Notes d origine:';
            $lines[] = $sourceNotes;
        }

        return implode("\n", $lines);
    }

    private function formatDate(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $value->format('Y-m-d') : null;
    }
}
