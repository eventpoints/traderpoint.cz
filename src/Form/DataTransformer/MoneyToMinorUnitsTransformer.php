<?php

declare(strict_types=1);

namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between a float/decimal major-unit value (e.g. 1234.56 CZK)
 * and an integer minor-unit value (e.g. 123456 haléř/cents).
 *
 * - model (DB/entity) uses int minor units
 * - view (form) shows decimal major units
 */
final readonly class MoneyToMinorUnitsTransformer implements DataTransformerInterface
{
    public function __construct(
        private int $fractionDigits = 2
    ) {}

    public function transform($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (! \is_int($value)) {
            // Be strict; fail loudly in dev if someone passes wrong types.
            throw new \LogicException('Expected integer minor units or null.');
        }

        $factor = 10 ** $this->fractionDigits;
        // format with fixed decimals but without thousands separator
        return number_format($value / $factor, $this->fractionDigits, '.', '');
    }

    public function reverseTransform($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        // Accept string/float-ish numbers from the form
        if (! \is_string($value) && ! \is_float($value) && ! \is_int($value)) {
            throw new \LogicException('Expected numeric string/float for money field.');
        }

        $normalized = (string) $value;
        // normalize comma to dot just in case
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            throw new \UnexpectedValueException('Invalid money value.');
        }

        $factor = 10 ** $this->fractionDigits;
        // round to avoid float artifacts
        return (int) round(((float) $normalized) * $factor);
    }
}
