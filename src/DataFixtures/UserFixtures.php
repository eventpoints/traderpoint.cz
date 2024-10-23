<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Service\AvatarService\AvatarService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface

{
    private const FAKE_USER_PASSWORD = '12345678';

    public function __construct(
        private readonly UserPasswordHasherInterface $userPasswordHasher,
        private readonly AvatarService $avatarService,
    )
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();
        for ($userCount = 0; $userCount < 50; $userCount++) {
            $email = $faker->email;
            $avatar = $this->avatarService->generate(hashString: $email);
            $user = new User(name: $faker->name, email: $email, avatar: $avatar, isVerified: true);
            $password = $this->userPasswordHasher->hashPassword(user: $user, plainPassword: self::FAKE_USER_PASSWORD);
            $user->setPassword($password);

            $this->addReference(name: "user_$userCount", object: $user);
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SkillFixtures::class,
        ];
    }
}
