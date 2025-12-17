<?php

namespace App\Controller\EasyAdmin;

use App\Entity\Engagement;
use App\Form\Type\MapLocationType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<Engagement>
 */
class EngagementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Engagement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->addFormTheme('admin/form/map_location.html.twig');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('address'),
            TextareaField::new('description'),
            BooleanField::new('isDeleted'),
            Field::new('mapLocation', 'Location')
                ->setFormType(MapLocationType::class)
                ->setFormTypeOptions([
                    'mapped' => true,
                    'height' => '380px',
                    'with_radius' => false,
                ])
                ->onlyOnForms(),
            MoneyField::new('budget')->setCurrency('CZK')->setStoredAsCents(),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
        ];
    }
}
