<?php
declare(strict_types=1);

namespace App\Form\DataTransformer;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Symfony\Component\Form\DataTransformerInterface;

final class FlowbiteDateTransformer implements DataTransformerInterface
{
    /** @var string */
    private $dateFormat;
    /** @var string|null */
    private $modelTimezone;

    public function __construct(string $dateFormat = 'Y-m-d', ?string $modelTimezone = null)
    {
        $this->dateFormat    = $dateFormat;
        $this->modelTimezone = $modelTimezone;
    }

    /**
     * @param DateTimeInterface|null $value
     * @return string
     */
    public function transform($value): string
    {
        if (!$value instanceof DateTimeInterface) {
            return '';
        }

        $dt = $value instanceof DateTimeImmutable ? $value : DateTimeImmutable::createFromMutable($value);

        if ($this->modelTimezone) {
            $dt = $dt->setTimezone(new DateTimeZone($this->modelTimezone));
        }
        return $dt->format($this->dateFormat);
    }

    /**
     * @param string|null $value
     */
    public function reverseTransform($value): ?DateTimeImmutable
    {
        $date = trim((string) $value);
        if ($date === '') {
            return null;
        }

        $tz = $this->modelTimezone ? new DateTimeZone($this->modelTimezone) : null;
        $dt = $tz
            ? DateTimeImmutable::createFromFormat($this->dateFormat, $date, $tz)
            : DateTimeImmutable::createFromFormat($this->dateFormat, $date);

        // Normalize time to midnight
        return $dt ? $dt->setTime(0, 0, 0) : null;
    }
}

