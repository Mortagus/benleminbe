<?php

declare(strict_types=1);

namespace App\Private\Music\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'music_genres')]
#[ORM\UniqueConstraint(name: 'uniq_music_genres_slug', columns: ['slug'])]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private string $name;

    #[ORM\Column(length: 180)]
    private string $slug;

    /**
     * @var Collection<int, ArtistGenre>
     */
    #[ORM\OneToMany(mappedBy: 'genre', targetEntity: ArtistGenre::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $artistGenres;

    public function __construct(string $name, string $slug)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->artistGenres = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, ArtistGenre>
     */
    public function getArtistGenres(): Collection
    {
        return $this->artistGenres;
    }
}
