<?php

declare(strict_types=1);

namespace App\Validator;

use InvalidArgumentException;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

final readonly class PhoneNumberValidator
{
    public function __construct(
        private ?PhoneNumberUtil $util = null
    ) {}

    /**
     * @return array{e164:string, countryCode:int, nsn:string}
     */
    public function normalize(int|string $prefix, string $number): array
    {
        $util = $this->util ?? PhoneNumberUtil::getInstance();

        $p = (int) preg_replace('/\D+/', '', (string) $prefix);
        $n = preg_replace('/\D+/', '', $number);

        if ($p <= 0 || $n === '') {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        // Build +<cc><nsn> and parse as “unknown region” (ZZ)
        $raw = '+' . $p . $n;
        $proto = $util->parse($raw, 'ZZ');

        if (! $util->isValidNumber($proto)) {
            throw new InvalidArgumentException('Invalid phone number.');
        }

        return [
            'e164' => $util->format($proto, PhoneNumberFormat::E164),    // +420777123456
            'countryCode' => $proto->getCountryCode(),                          // 420
            'nsn' => (string) $proto->getNationalNumber(),              // 777123456
        ];
    }
}
