<?php

namespace App\ViewModel;

use Symfony\Component\Uid\Uuid;

final readonly class MainSkillCategory
{
    public function __construct(
        private Uuid $id,
        private string $title,
        private string $imagePath,
    )
    {
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }
}