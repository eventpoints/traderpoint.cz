<?php

namespace App\Service;

final class ReferenceGenerator
{
    public function next(string $prefix = 'MCT'): string
    {
        return sprintf('%s-%s', $prefix, strtoupper(substr(bin2hex(random_bytes(4)), 0, 5)));
    }
}