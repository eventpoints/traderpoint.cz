<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(UuidGenerator::class)]
    private null|Uuid $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * @var Collection<int, UserSkill>
     */
    #[ORM\OneToMany(targetEntity: UserSkill::class, mappedBy: 'skill')]
    private Collection $users;

    /**
     * @var Collection<int, Skill>
     */
    #[ORM\OneToMany(targetEntity: Skill::class, mappedBy: 'trade',cascade: ['persist'])]
    private Collection $skills;

    #[ORM\ManyToOne(targetEntity: Skill::class, inversedBy: 'skills')]
    #[ORM\JoinColumn(name: 'trade_id', referencedColumnName: 'id')]
    private Skill|null $trade = null;

    /**
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->users = new ArrayCollection();
        $this->skills = new ArrayCollection();
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
     * @return Collection<int, UserSkill>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(UserSkill $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setSkill($this);
        }

        return $this;
    }

    public function removeUser(UserSkill $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getSkill() === $this) {
                $user->setSkill(null);
            }
        }

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
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);
        return $this;
    }

}
