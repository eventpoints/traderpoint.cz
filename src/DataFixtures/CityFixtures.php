<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Data\CityData;
use App\Entity\City;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CityFixtures extends Fixture
{

    public function load(ObjectManager $manager): void
    {
        $cities = CityData::getCzechCities();
        foreach ($cities as $cityData) {
            $city = new City(name: $cityData['name'], latitude: $cityData['latitude'], longitude: $cityData['longitude']);
            $manager->persist($city);
            $this->addReference(name: $cityData['name'], object: $city);
        }
        $manager->flush();
    }

}
