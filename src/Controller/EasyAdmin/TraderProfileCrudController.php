<?php

namespace App\Controller\EasyAdmin;

use App\Entity\TraderProfile;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<TraderProfile>
 */
class TraderProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TraderProfile::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('companyNumber'),
            AssociationField::new('skills')->onlyOnForms(),
            AssociationField::new('owner'),
            CollectionField::new('skills')->onlyOnIndex()
                ->setTemplatePath('admin/field/skills_badges.html.twig')
                ->setSortable(false),
        ];
    }
}
