<?php

namespace App\DataFixtures;

use App\Entity\Review;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class ReviewFixtures extends Fixture implements DependentFixtureInterface
{

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($reviewCount = 0; $reviewCount < 500; $reviewCount++) {
            $responseRating = $this->getRandomFloat();
            $customerServicesRating = $this->getRandomFloat();
            $workQualityRating = $this->getRandomFloat();
            $valueForMoneyRating = $this->getRandomFloat();
            $reviewerCount = random_int(0, 49);
            $revieweeCount = random_int(0, 49);
            $reviewer = $this->getReference("user_$reviewerCount");
            $reviewee = $this->getReference("user_$revieweeCount");

            $review = new Review(
                title: $faker->text(random_int(5, 20)),
                content: $faker->realText,
                responseRating: (string)$responseRating,
                customerServicesRating: (string)$customerServicesRating,
                workQualityRating: (string)$workQualityRating,
                valueForMoneyRating: (string)$valueForMoneyRating,
                reviewer: $reviewer,
                reviewee: $reviewee
            );
            $overallRating = round(($responseRating + $customerServicesRating + $workQualityRating + $valueForMoneyRating) / 4, 2);
            $review->setOverallRating((string)$overallRating);
            $manager->persist($review);
            $this->addReference(name: "review_$reviewCount", object: $review);
        }

        $manager->flush();
    }

    private
    function getRandomFloat(float $min = 0.0, float $max = 5.0): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }


    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }

}
