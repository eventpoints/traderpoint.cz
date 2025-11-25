<?php

declare(strict_types=1);

namespace App\ViewModel;

final readonly class FaqModelView
{
    /**
     * @param array<string, string|int|float> $parameters
     */
    public function __construct(
        private string $questionKey,
        private string $answerKey,
        private array $parameters = [],
    ) {
    }

    public function getQuestionKey(): string
    {
        return $this->questionKey;
    }

    public function getAnswerKey(): string
    {
        return $this->answerKey;
    }

    /**
     * @return array<string, string|int|float>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
