<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class ReverseGeocoder
{
    public function __construct(
        private HttpClientInterface $http
    ) {}

    public function lookup(float $lat, float $lng): ?string
    {
        $resp = $this->http->request('GET', 'https://nominatim.openstreetmap.org/reverse', [
            'query' => [
                'format' => 'jsonv2',
                'lat' => $lat,
                'lon' => $lng,
                'addressdetails' => 1,
            ],
            // Nominatim requires a descriptive UA with contact info
            'headers' => [
                'User-Agent' => 'YourAppName/1.0 (contact: you@example.com)',
            ],
        ]);

        if (200 !== $resp->getStatusCode()) {
            return null;
        }

        $data = $resp->toArray(false);
        return $data['display_name'] ?? null;
    }
}
