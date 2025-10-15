<?php

namespace App\DataTransferObject;

final class LoginFormDto
{
    private null|string $email = null;
    private null|string $password = null;
    private bool $isRememberMe = false;

    /**
     * @param string|null $email
     */
    public function __construct(?string $email)
    {
        $this->email = $email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): void
    {
        $this->email = $email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): void
    {
        $this->password = $password;
    }

    public function isRememberMe(): bool
    {
        return $this->isRememberMe;
    }

    public function setIsRememberMe(bool $isRememberMe): void
    {
        $this->isRememberMe = $isRememberMe;
    }

}