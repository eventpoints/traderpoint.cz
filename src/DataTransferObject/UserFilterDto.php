<?php

namespace App\DataTransferObject;

final readonly class UserFilterDto
{
    private string $keyword;

    /**
     * @param string $keyword
     */
    public function __construct(string $keyword)
    {
        $this->keyword = $keyword;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

}