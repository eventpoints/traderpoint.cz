<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'skills')]
    private Collection $users;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\OneToMany(targetEntity: Skill::class, mappedBy: 'trade', cascade: ['persist'])]
    private Collection $skills;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'trade_id', referencedColumnName: 'id')]
    private Skill|null $trade = null;

    /**
     * @var Collection<int, Engagement>
     */
    #[ORM\ManyToMany(targetEntity: Engagement::class, mappedBy: 'skills')]
    private Collection $engagements;

    public function __construct(
        #[ORM\Column(length: 255)]
        private string $name
    )
    {
        $this->id = Uuid::v4();
        $this->users = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->engagements = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (! $this->users->contains($user)) {
            $this->users->add($user);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        $this->users->removeElement($user);
        return $this;
    }

    public function getTrade(): ?Skill
    {
        return $this->trade;
    }

    public function setTrade(?Skill $trade): void
    {
        $this->trade = $trade;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): static
    {
        if (! $this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    /**
     * @return Collection<int, Engagement>
     */
    public function getEngagements(): Collection
    {
        return $this->engagements;
    }

    public function addEngagement(Engagement $engagement): static
    {
        if (! $this->engagements->contains($engagement)) {
            $this->engagements->add($engagement);
            $engagement->addSkill($this);
        }

        return $this;
    }

    public function removeEngagement(Engagement $engagement): static
    {
        if ($this->engagements->removeElement($engagement)) {
            $engagement->removeSkill($this);
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->name;
    }
}