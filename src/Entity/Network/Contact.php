<?php

declare(strict_types=1);

namespace App\Entity\Network;

use App\Enum\Network\ContactPriority;
use App\Enum\Network\ContactRelationshipStatus;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'network_contacts')]
class Contact
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\Column(length: 180)]
    private string $displayName;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $organization = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $role = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $mainChannel = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $email = [];

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $phone = [];

    #[ORM\Column(length: 2048, nullable: true)]
    private ?string $profileUrl = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(enumType: ContactPriority::class, length: 32)]
    private ContactPriority $priority;

    #[ORM\Column(enumType: ContactRelationshipStatus::class, length: 32)]
    private ContactRelationshipStatus $relationshipStatus;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastContactAt = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $nextActionAt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $nextAction = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $tags = [];

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @var Collection<int, Interaction>
     */
    #[ORM\OneToMany(mappedBy: 'contact', targetEntity: Interaction::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['date' => 'DESC', 'createdAt' => 'DESC'])]
    private Collection $interactions;

    public function __construct(string $id, string $displayName)
    {
        $this->id = $id;
        $this->displayName = $displayName;
        $this->priority = ContactPriority::default();
        $this->relationshipStatus = ContactRelationshipStatus::default();
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
        $this->interactions = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName !== null && $firstName !== '' ? $firstName : null;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName !== null && $lastName !== '' ? $lastName : null;

        return $this;
    }

    public function getOrganization(): ?string
    {
        return $this->organization;
    }

    public function setOrganization(?string $organization): self
    {
        $this->organization = $organization !== null && $organization !== '' ? $organization : null;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(?string $role): self
    {
        $this->role = $role !== null && $role !== '' ? $role : null;

        return $this;
    }

    public function getMainChannel(): ?string
    {
        return $this->mainChannel;
    }

    public function setMainChannel(?string $mainChannel): self
    {
        $this->mainChannel = $mainChannel !== null && $mainChannel !== '' ? $mainChannel : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getEmails(): array
    {
        return $this->email;
    }

    /**
     * @param array<string>|string|null $email
     */
    public function setEmail(array|string|null $email): self
    {
        return $this->setEmails($this->normalizeMultiValue($email));
    }

    /**
     * @param list<string> $emails
     */
    public function setEmails(array $emails): self
    {
        $this->email = $this->normalizeMultiValue($emails);

        return $this;
    }

    public function addEmail(string $email): self
    {
        return $this->setEmails(array_merge($this->email, [$email]));
    }

    public function getEmail(): ?string
    {
        return $this->email[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function getPhones(): array
    {
        return $this->phone;
    }

    /**
     * @param array<string>|string|null $phone
     */
    public function setPhone(array|string|null $phone): self
    {
        return $this->setPhones($this->normalizeMultiValue($phone));
    }

    /**
     * @param list<string> $phones
     */
    public function setPhones(array $phones): self
    {
        $this->phone = $this->normalizeMultiValue($phones);

        return $this;
    }

    public function addPhone(string $phone): self
    {
        return $this->setPhones(array_merge($this->phone, [$phone]));
    }

    public function getPhone(): ?string
    {
        return $this->phone[0] ?? null;
    }

    public function getProfileUrl(): ?string
    {
        return $this->profileUrl;
    }

    public function setProfileUrl(?string $profileUrl): self
    {
        $this->profileUrl = $profileUrl !== null && $profileUrl !== '' ? $profileUrl : null;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source !== null && $source !== '' ? $source : null;

        return $this;
    }

    public function getPriority(): ContactPriority
    {
        return $this->priority;
    }

    public function setPriority(ContactPriority $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function getPriorityLabel(): string
    {
        return $this->priority->label();
    }

    public function getRelationshipStatus(): ContactRelationshipStatus
    {
        return $this->relationshipStatus;
    }

    public function setRelationshipStatus(ContactRelationshipStatus $relationshipStatus): self
    {
        $this->relationshipStatus = $relationshipStatus;

        return $this;
    }

    public function getRelationshipStatusLabel(): string
    {
        return $this->relationshipStatus->label();
    }

    public function getLastContactAt(): ?DateTimeImmutable
    {
        return $this->lastContactAt;
    }

    public function setLastContactAt(?DateTimeImmutable $lastContactAt): self
    {
        $this->lastContactAt = $lastContactAt;

        return $this;
    }

    public function getNextActionAt(): ?DateTimeImmutable
    {
        return $this->nextActionAt;
    }

    public function setNextActionAt(?DateTimeImmutable $nextActionAt): self
    {
        $this->nextActionAt = $nextActionAt;

        return $this;
    }

    public function getNextAction(): ?string
    {
        return $this->nextAction;
    }

    public function setNextAction(?string $nextAction): self
    {
        $this->nextAction = $nextAction !== null && $nextAction !== '' ? $nextAction : null;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes !== null && $notes !== '' ? $notes : null;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param list<string> $tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = array_values(array_unique(array_filter(array_map(
            static fn (mixed $tag): string => trim((string) $tag),
            $tags,
        ), static fn (string $tag): bool => $tag !== '')));

        return $this;
    }

    public function addTag(string $tag): self
    {
        $tag = trim($tag);
        if ($tag !== '' && !in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }

        return $this;
    }

    public function removeTag(string $tag): self
    {
        $this->tags = array_values(array_filter(
            $this->tags,
            static fn (string $existingTag): bool => $existingTag !== $tag,
        ));

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Interaction>
     */
    public function getInteractions(): Collection
    {
        return $this->interactions;
    }

    public function addInteraction(Interaction $interaction): self
    {
        if (!$this->interactions->contains($interaction)) {
            $this->interactions->add($interaction);
            $interaction->setContact($this);
        }

        return $this;
    }

    public function removeInteraction(Interaction $interaction): self
    {
        if ($this->interactions->removeElement($interaction) && $interaction->getContact() === $this) {
            $interaction->setContact(null);
        }

        return $this;
    }

    /**
     * @param array<string>|string|null $values
     *
     * @return list<string>
     */
    private function normalizeMultiValue(array|string|null $values): array
    {
        if ($values === null) {
            return [];
        }

        if (is_string($values)) {
            $values = [$values];
        }

        $normalized = [];

        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '' && !in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
