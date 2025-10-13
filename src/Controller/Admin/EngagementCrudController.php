<?php

namespace App\Controller\Admin;

use App\Entity\Engagement;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class EngagementCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Engagement::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title')->hideOnForm(),
            TextareaField::new('description')->hideOnIndex(),
            NumberField::new('latitude'),
            NumberField::new('longitude'),
            TextField::new('address'),
            DateTimeField::new('createdAt'),
            DateTimeField::new('updatedAt'),
            AssociationField::new('owner')->autocomplete(),
            AssociationField::new('skills')->setFormTypeOptions(['by_reference' => false])->autocomplete(),
            AssociationField::new('quotes')->setFormTypeOptions(['by_reference' => false])->autocomplete(),
            AssociationField::new('quote')->autocomplete(),
            AssociationField::new('payments')->setFormTypeOptions(['by_reference' => false])->autocomplete(),
            NumberField::new('distanceFromNearestCity'),
        ];
    }
}
