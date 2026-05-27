<?php

declare(strict_types=1);

namespace App\Entity\Network;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'network_import_logs')]
class ImportLog
{
    #[ORM\Id]
    #[ORM\Column(length: 120)]
    private string $id;

    #[ORM\Column(length: 180)]
    private string $sourceLabel;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Column]
    private int $created = 0;

    #[ORM\Column]
    private int $updated = 0;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $importedAt;

    /**
     * @var list<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $errors = [];

    public function __construct(string $id, string $sourceLabel)
    {
        $this->id = $id;
        $this->sourceLabel = $sourceLabel;
        $this->importedAt = new DateTimeImmutable();
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

    public function getSourceLabel(): string
    {
        return $this->sourceLabel;
    }

    public function setSourceLabel(string $sourceLabel): self
    {
        $this->sourceLabel = $sourceLabel;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getCreated(): int
    {
        return $this->created;
    }

    public function setCreated(int $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): int
    {
        return $this->updated;
    }

    public function setUpdated(int $updated): self
    {
        $this->updated = $updated;

        return $this;
    }

    public function getImportedAt(): DateTimeImmutable
    {
        return $this->importedAt;
    }

    public function setImportedAt(DateTimeImmutable $importedAt): self
    {
        $this->importedAt = $importedAt;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param list<string> $errors
     */
    public function setErrors(array $errors): self
    {
        $this->errors = array_values(array_filter(array_map(
            static fn (mixed $error): string => trim((string) $error),
            $errors,
        ), static fn (string $error): bool => $error !== ''));

        return $this;
    }

    public function addError(string $error): self
    {
        $error = trim($error);
        if ($error !== '') {
            $this->errors[] = $error;
        }

        return $this;
    }
}
