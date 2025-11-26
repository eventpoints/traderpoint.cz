<?php

declare(strict_types=1);

namespace App\Validator;

use App\Validator\Constraint\CompanyNumberConstraintValidator;
use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_METHOD)]
class CompanyNumberValidator extends Constraint
{
    public string $messageInvalidFormat = 'trader.company_number.invalid_format';

    public string $messageInvalidChecksum = 'trader.company_number.invalid_checksum';

    /**
     * Name of the field on the same object that holds the country code (e.g. "country").
     */
    public ?string $countryField = null;

    public function __construct(
        ?string $countryField = null,
        ?string $messageInvalidFormat = null,
        ?string $messageInvalidChecksum = null,
        mixed $options = null,
        ?array $groups = null,
        mixed $payload = null,
    ) {
        parent::__construct($options ?? [], $groups, $payload);

        if ($countryField !== null) {
            $this->countryField = $countryField;
        }

        if ($messageInvalidFormat !== null) {
            $this->messageInvalidFormat = $messageInvalidFormat;
        }

        if ($messageInvalidChecksum !== null) {
            $this->messageInvalidChecksum = $messageInvalidChecksum;
        }
    }

    public function validatedBy(): string
    {
        return CompanyNumberConstraintValidator::class;
    }
}
