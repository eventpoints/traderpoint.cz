<?php

namespace App\Repository;

use App\Entity\Skill;
use App\ViewModel\MainSkillCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Order;
use Doctrine\Persistence\ManagerRegistry;
use Nette\Utils\Strings;

/**
 * @extends ServiceEntityRepository<Skill>
 */
class SkillRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Skill::class);
    }

    /**
     * @return MainSkillCategory[]
     */
    public function findPrimarySkillsForLandingPage(): array
    {
        $names = ['skill.builder', 'skill.plumber', 'skill.electrician'];
        $qb = $this->createQueryBuilder('skill');
        $qb->where('LOWER(skill.name) IN (:names)')
            ->setParameter('names', $names)
            ->addOrderBy('skill.name', Order::Ascending->value);

           $result = $qb->getQuery()
               ->getResult();

           $skills = new ArrayCollection();
           foreach ($result as $item){
              $mainSkillCategory = new MainSkillCategory(
                  id: $item->getId(),
                  title: $item->getName(),
                  imagePath: 'images/landing/' . Strings::webalize($item->getName()) . '.png',
              );
               $skills->add($mainSkillCategory);
           }

           return $skills->toArray();
    }
}
