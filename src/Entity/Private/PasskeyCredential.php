<?php

declare(strict_types=1);

namespace App\Entity\Private;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\CredentialRecord;
use Webauthn\TrustPath\CertificateTrustPath;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\TrustPath\TrustPath;
use Webauthn\Util\Base64;

#[ORM\Entity]
#[ORM\Table(name: 'private_passkey_credentials')]
#[ORM\UniqueConstraint(name: 'uniq_private_passkey_credential_id', columns: ['public_key_credential_id'])]
class PasskeyCredential
{
    public const DEFAULT_LABEL = 'Passkey';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(name: 'public_key_credential_id', length: 512)]
    private string $publicKeyCredentialId;

    #[ORM\Column(length: 180)]
    private string $displayName;

    #[ORM\Column(length: 64)]
    private string $userHandle;

    #[ORM\Column(length: 32)]
    private string $type;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $transports = [];

    #[ORM\Column(length: 64)]
    private string $attestationType;

    /**
     * @var array{type: string, certificates?: list<string>}
     */
    #[ORM\Column(type: Types::JSON)]
    private array $trustPath = ['type' => 'empty'];

    #[ORM\Column(length: 36)]
    private string $aaguid;

    #[ORM\Column(type: Types::TEXT)]
    private string $credentialPublicKey;

    #[ORM\Column]
    private int $counter = 0;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $otherUi = null;

    #[ORM\Column(nullable: true)]
    private ?bool $backupEligible = null;

    #[ORM\Column(nullable: true)]
    private ?bool $backupStatus = null;

    #[ORM\Column(nullable: true)]
    private ?bool $uvInitialized = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public static function fromCredentialRecord(
        CredentialRecord $credentialRecord,
        string $displayName,
        DateTimeImmutable $createdAt,
    ): self {
        $credential = new self();
        $credential->createdAt = $createdAt;
        $credential->applyCredentialRecord($credentialRecord);
        $credential->displayName = self::normalizeDisplayName($displayName);
        $credential->lastUsedAt = null;

        return $credential;
    }

    public function applyCredentialRecord(CredentialRecord $credentialRecord): void
    {
        $this->publicKeyCredentialId = Base64UrlSafe::encodeUnpadded($credentialRecord->publicKeyCredentialId);
        $this->userHandle = $credentialRecord->userHandle;
        $this->type = $credentialRecord->type;
        $this->transports = array_values($credentialRecord->transports);
        $this->attestationType = $credentialRecord->attestationType;
        $this->trustPath = self::serializeTrustPath($credentialRecord->trustPath);
        $this->aaguid = $credentialRecord->aaguid->toRfc4122();
        $this->credentialPublicKey = Base64UrlSafe::encodeUnpadded($credentialRecord->credentialPublicKey);
        $this->counter = $credentialRecord->counter;
        $this->otherUi = $credentialRecord->otherUI;
        $this->backupEligible = $credentialRecord->backupEligible;
        $this->backupStatus = $credentialRecord->backupStatus;
        $this->uvInitialized = $credentialRecord->uvInitialized;
    }

    public function toCredentialRecord(): CredentialRecord
    {
        return CredentialRecord::create(
            Base64::decode($this->publicKeyCredentialId),
            $this->type,
            $this->transports,
            $this->attestationType,
            self::deserializeTrustPath($this->trustPath),
            \Symfony\Component\Uid\Uuid::fromString($this->aaguid),
            Base64::decode($this->credentialPublicKey),
            $this->userHandle,
            $this->counter,
            $this->otherUi,
            $this->backupEligible,
            $this->backupStatus,
            $this->uvInitialized,
        );
    }

    public function updateFromCredentialRecord(CredentialRecord $credentialRecord, DateTimeImmutable $lastUsedAt): void
    {
        $this->applyCredentialRecord($credentialRecord);
        $this->lastUsedAt = $lastUsedAt;
    }

    public function rename(string $displayName): void
    {
        $this->displayName = self::normalizeDisplayName($displayName);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPublicKeyCredentialId(): string
    {
        return $this->publicKeyCredentialId;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function getUserHandle(): string
    {
        return $this->userHandle;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return list<string>
     */
    public function getTransports(): array
    {
        return $this->transports;
    }

    public function getAttestationType(): string
    {
        return $this->attestationType;
    }

    /**
     * @return array{type: string, certificates?: list<string>}
     */
    public function getTrustPathData(): array
    {
        return $this->trustPath;
    }

    public function getAaguid(): string
    {
        return $this->aaguid;
    }

    public function getCounter(): int
    {
        return $this->counter;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    private static function normalizeDisplayName(string $displayName): string
    {
        $displayName = trim($displayName);

        return $displayName !== '' ? $displayName : self::DEFAULT_LABEL;
    }

    /**
     * @return array{type: string, certificates?: list<string>}
     */
    private static function serializeTrustPath(TrustPath $trustPath): array
    {
        if ($trustPath instanceof CertificateTrustPath) {
            return [
                'type' => 'certificate',
                'certificates' => array_values($trustPath->certificates),
            ];
        }

        return ['type' => 'empty'];
    }

    private static function deserializeTrustPath(array $trustPath): TrustPath
    {
        return match ($trustPath['type'] ?? 'empty') {
            'certificate' => CertificateTrustPath::create($trustPath['certificates'] ?? []),
            default => EmptyTrustPath::create(),
        };
    }
}
