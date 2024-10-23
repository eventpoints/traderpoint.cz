<?php

namespace App\DataTransferObject;

final readonly class UserFilterDto
{
    public function __construct(
        private string $keyword
    )
    {
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }
}