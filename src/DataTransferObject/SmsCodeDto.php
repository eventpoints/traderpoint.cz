<?php

namespace App\DataTransferObject;

final class SmsCodeDto
{
    private int $digit1;
    private int $digit2;
    private int $digit3;
    private int $digit4;
    private int $digit5;
    private int $digit6;

    public function getDigit1(): int
    {
        return $this->digit1;
    }

    public function setDigit1(int $digit1): void
    {
        $this->digit1 = $digit1;
    }

    public function getDigit2(): int
    {
        return $this->digit2;
    }

    public function setDigit2(int $digit2): void
    {
        $this->digit2 = $digit2;
    }

    public function getDigit3(): int
    {
        return $this->digit3;
    }

    public function setDigit3(int $digit3): void
    {
        $this->digit3 = $digit3;
    }

    public function getDigit4(): int
    {
        return $this->digit4;
    }

    public function setDigit4(int $digit4): void
    {
        $this->digit4 = $digit4;
    }

    public function getDigit5(): int
    {
        return $this->digit5;
    }

    public function setDigit5(int $digit5): void
    {
        $this->digit5 = $digit5;
    }

    public function getDigit6(): int
    {
        return $this->digit6;
    }

    public function setDigit6(int $digit6): void
    {
        $this->digit6 = $digit6;
    }

}