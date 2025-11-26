<?php
declare(strict_types=1);

namespace App\Validator\Constraint;

use App\Validator\CompanyNumberValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class CompanyNumberConstraintValidator extends ConstraintValidator
{
    /**
     * @param mixed $value
     */
    public function validate($value, Constraint $constraint): void
    {
        if (! $constraint instanceof CompanyNumberValidator) {
            throw new UnexpectedTypeException($constraint, CompanyNumberValidator::class);
        }

        // Empty -> nothing to validate (field is optional)
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        // Get the owning object (TraderProfile) and derive country
        $object = $this->context->getObject();

        $country = null;

        if ($constraint->countryField !== null && \is_object($object)) {
            $getter = 'get' . ucfirst($constraint->countryField);
            if (method_exists($object, $getter)) {
                $country = $object->$getter();
            }
        }

        // If not CZ/CZE, we don't validate here (could extend later for other countries)
        if (!\in_array($country, ['CZ', 'CZE'], true)) {
            return;
        }

        // Normalise: remove spaces
        $raw = preg_replace('/\s+/', '', $value);

        // Czech IČO: exactly 8 digits
        if (!preg_match('/^\d{8}$/', $raw)) {
            $this->context
                ->buildViolation($constraint->messageInvalidFormat)
                ->addViolation();

            return;
        }

        if (!self::isValidCzechIco($raw)) {
            $this->context
                ->buildViolation($constraint->messageInvalidChecksum)
                ->addViolation();
        }
    }

    private static function isValidCzechIco(string $ico): bool
    {
        if (!preg_match('/^\d{8}$/', $ico)) {
            return false;
        }

        $sum = 0;
        // weights 8–2 for first 7 digits
        for ($i = 0; $i < 7; $i++) {
            $sum += (int) $ico[$i] * (8 - $i);
        }

        $mod = $sum % 11;

        if ($mod === 0) {
            $check = 1;
        } elseif ($mod === 1) {
            $check = 0;
        } else {
            $check = 11 - $mod;
        }

        return (int) $ico[7] === $check;
    }
}
