<?php

declare(strict_types=1);

namespace App\Twig\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Badge')]
final class Badge
{
    public string $text;

    public string $variant = 'secondary';

    public bool $pill = true;

    public bool $isOutline = false;

    public ?string $icon = null;

    /**
     * sm|md|lg
     */
    public string $size = 'md';

    public function getClasses(): string
    {
        $classes = ['badge'];

        // size classes using Bootstrap utilities
        $classes = array_merge($classes, $this->getSizeClasses());

        $classes[] = 'fw-normal';
        if ($this->isOutline) {
            $classes[] = 'border';
            $classes[] = "border-{$this->variant}";
            $classes[] = "text-{$this->variant}";
            $classes[] = 'bg-transparent';
        } else {
            $classes[] = "text-bg-{$this->variant}";
        }

        if ($this->pill) {
            $classes[] = 'rounded-pill';
        }

        return implode(' ', $classes);
    }

    /**
     * @return list<string>
     */
    private function getSizeClasses(): array
    {
        return match ($this->size) {
            'sm' => ['px-2', 'py-0', 'small'],         // compact
            'lg' => ['px-3', 'py-2', 'fs-6'],         // chunkier
            'xl-lg' => ['px-3', 'py-3', 'fs-5'],         // chunkier
            default => ['px-2', 'py-1', 'fs-6'],      // md (default)
        };
    }
}
