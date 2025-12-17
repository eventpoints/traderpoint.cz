<?php

namespace App\Controller\EasyAdmin;

use App\Entity\AdvertisingCampaign;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AdvertisingCampaignCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AdvertisingCampaign::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('code')
                ->setHelp('Unique short code for this tracked link'),
            BooleanField::new('isActive')
                ->setLabel('Active'),
            DateTimeField::new('createdAt')
                ->hideOnForm(),
            DateTimeField::new('updatedAt')
                ->hideOnForm()
                ->hideOnIndex(),
        ];
    }

}
