<?php

namespace App\DataTransferObject;

use App\Entity\Skill;
use Doctrine\Common\Collections\ArrayCollection;

final class UserTraderDto
{
    private string $firstName;

    private string $lastName;

    /**
     * @var ArrayCollection<int, Skill>
     */
    private ArrayCollection $skills;

    private string $email;

    private string $plainPassword;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return ArrayCollection<int, Skill>
     */
    public function getSkills(): ArrayCollection
    {
        return $this->skills;
    }

    /**
     * @param ArrayCollection<int, Skill> $skills
     */
    public function setSkills(ArrayCollection $skills): void
    {
        $this->skills = $skills;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPlainPassword(): string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }
}