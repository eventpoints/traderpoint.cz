<?php

namespace App\DataFixtures;

use App\DataFixtures\Data\SkillData;
use App\Entity\Skill;
use App\Entity\TraderProfile;
use App\Entity\User;
use App\Enum\TraderStatusEnum;
use App\Enum\UserRoleEnum;
use App\Service\AvatarService\AvatarService;
use Carbon\CarbonImmutable;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserFixtures extends Fixture implements DependentFixtureInterface
{
    private const FAKE_USER_PASSWORD = '12345678';

    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly AvatarService $avatarService,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('cs_CZ');

        for ($i = 0; $i < 25; $i++) {
            $email = $faker->unique()->safeEmail();
            $avatar = $this->avatarService->generate(hashString: $email);

            $user = new User();
            $user->setFirstName($faker->firstName());
            $user->setLastName($faker->lastName());
            $user->setEmail($email);
            $user->setAvatar($avatar);
            $user->setVerifiedAt(CarbonImmutable::now());
            $user->setPassword($this->passwordHasher->hashPassword($user, self::FAKE_USER_PASSWORD));

            // 20% traders, 80% clients
            $isTrader = $faker->boolean(20);

            if ($isTrader) {
                // Collect existing Skill references
                $skillPool = [];
                foreach (SkillData::getSkills() as $skills) {
                    foreach ($skills as $skillRef) {
                        if ($this->hasReference($skillRef, Skill::class)) {
                            /** @var Skill $skill */
                            $skill = $this->getReference($skillRef, Skill::class);
                            $skillPool[] = $skill;
                        }
                    }
                }

                $profile = new TraderProfile();
                $profile->setOwner($user);        // owning side
                $profile->setTitle($faker->jobTitle());
                $profile->setAvatar($avatar);
                $profile->setStatus(TraderStatusEnum::ACTIVE);

                if ($skillPool !== []) {
                    $toAdd = $faker->randomElements(
                        $skillPool,
                        $faker->numberBetween(1, min(3, \count($skillPool)))
                    );
                    foreach ($toAdd as $skill) {
                        $profile->addSkill($skill);
                    }
                }

                $user->setTraderProfile($profile); // inverse side kept in sync
                $user->setRoles([UserRoleEnum::ROLE_TRADER->value]);
            } else {
                // client-only; leave roles empty, getRoles() will add ROLE_USER
                $user->setRoles([]);
            }

            $this->addReference("user_{$i}", $user);
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
