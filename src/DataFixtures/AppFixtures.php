<?php

namespace App\DataFixtures;

use App\Entity\Review;
use App\Entity\Skill;
use App\Entity\User;
use App\Entity\UserSkill;
use App\Repository\SkillRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private const FAKE_USER_PASSWORD = '12345678';

    public function __construct(
        private UserPasswordHasherInterface $userPasswordHasher,
        private SkillRepository             $skillRepository
    )
    {
    }


    public function load(ObjectManager $manager): void
    {
        foreach ($this->getSkills() as $trade => $skills) {
            $tradeEntity = new Skill(name: $trade);
            foreach ($skills as $skill) {
                $skillEntity = new Skill(name: $skill);
                $skillEntity->setTrade($tradeEntity);
                $tradeEntity->addSkill($skillEntity);
            }

            $manager->persist($tradeEntity);
        }
        $manager->flush();


        $faker = Factory::create();
        for ($userCount = 0; $userCount < 50; $userCount++) {
            $user = new User(name: $faker->name, email: $faker->email, isVerified: true);
            $password = $this->userPasswordHasher->hashPassword(user: $user, plainPassword: self::FAKE_USER_PASSWORD);
            $user->setPassword($password);

            for ($reviewCount = 0; $reviewCount < random_int(1, 15); $reviewCount++) {
                $responseRating = $this->getRandomFloat();
                $customerServicesRating = $this->getRandomFloat();
                $workQualityRating = $this->getRandomFloat();
                $valueForMoneyRating = $this->getRandomFloat();

                $review = new Review(
                    title: $faker->text(random_int(5, 20)),
                    content: $faker->realText,
                    responseRating: (string)$responseRating,
                    customerServicesRating: (string)$customerServicesRating,
                    workQualityRating: (string)$workQualityRating,
                    valueForMoneyRating: (string)$valueForMoneyRating,
                    owner: $user
                );
                $overallRating = round(($responseRating + $customerServicesRating + $workQualityRating + $valueForMoneyRating) / 4, 2);
                $review->setOverallRating((string) $overallRating);
                $user->addReview($review);
            }


            for ($skillCount = 0; $skillCount < random_int(1, 3); $skillCount++) {

                $randomCategoryKey = array_rand($this->getSkills());
                $randomProfession = $this->getSkills()[$randomCategoryKey][array_rand($this->getSkills()[$randomCategoryKey])];

                $userSkill = new UserSkill(
                    owner: $user,
                    skill: $this->skillRepository->findOneBy(['name' => $randomProfession])
                );
                $user->addSkill($userSkill);
            }

            $manager->persist($user);
        }

        $manager->flush();
    }

    private function getRandomFloat(float $min = 0.0, float $max = 5.0): float
    {
        return round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);
    }


    /**
     * @return array<string, array<int, string>>
     */
    private function getSkills(): array
    {
        return [
            'Home Improvement & Maintenance' => [
                'Plumber',
                'Electrician',
                'Carpenter',
                'Painter and Decorator',
                'Roofer',
                'Handyman',
                'Heating and Air Conditioning Technician',
                'Landscaper and Gardener',
                'Tile Setter',
                'Window Cleaner',
            ],
            'Construction & Building' => [
                'Builder',
                'Bricklayer',
                'Drywall Installer',
                'Plasterer',
                'Floor Installer',
                'General Contractor',
                'Surveyor',
            ],
            'Creative & Digital Services' => [
                'Graphic Designer',
                'Web Developer',
                'Photographer',
                'Videographer',
                'Writer/Copywriter',
                'Marketing Consultant',
                'Social Media Manager',
                'SEO Specialist',
            ],
            'Automotive Services' => [
                'Mechanic',
                'Auto Electrician',
                'Detailer (Car Cleaning)',
                'Tow Truck Operator',
                'Window Tinting Specialist',
                'Tyre Specialist',
            ],
            'Health & Wellness' => [
                'Personal Trainer',
                'Massage Therapist',
                'Dietitian/Nutritionist',
                'Physical Therapist',
                'Yoga Instructor',
                'Mental Health Counselor',
            ],
            'Beauty & Grooming' => [
                'Hairdresser',
                'Barber',
                'Makeup Artist',
                'Nail Technician',
                'Beautician',
                'Tattoo Artist',
            ],
            'Events & Entertainment' => [
                'DJ',
                'Musician',
                'Event Planner',
                'Caterer',
                'Photobooth Operator',
                'Balloon Artist',
                'Magician',
            ],
            'Education & Tutoring' => [
                'Language Tutor',
                'Music Teacher',
                'Math/Science Tutor',
                'Test Prep Coach',
                'Fitness Trainer',
            ],
            'Legal & Financial Services' => [
                'Accountant',
                'Tax Preparer',
                'Lawyer (solo practitioner)',
                'Notary',
                'Financial Advisor',
            ],
            'Miscellaneous Services' => [
                'Dog Walker',
                'Pet Sitter',
                'Cleaner',
                'House Sitter',
                'Tailor',
                'Interior Designer',
                'Pest Control Technician',
            ],
        ];
    }
}
