<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReactionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: ReactionRepository::class)]
class Reaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private ?Uuid $id = null;

    // e.g. "needs_more_info", "budget_too_low"
    #[ORM\Column(length: 64, unique: true)]
    private string $code;

    // e.g. "Need more info", "Budget too low"
    #[ORM\Column(length: 64)]
    private string $label;

    // Optional explanation / tooltip.
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    // For UI – emoji or icon key, e.g. "❓" or "tp:budget-low"
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(type: 'integer', options: [
        'default' => 0,
    ])]
    private int $sortOrder = 0;

    #[ORM\Column(type: 'boolean', options: [
        'default' => true,
    ])]
    private bool $active = true;

    #[ORM\Column(length: 32, nullable: true)]
    private ?string $color = null;

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    public function setSortOrder(int $sortOrder): self
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;

        return $this;
    }
}
