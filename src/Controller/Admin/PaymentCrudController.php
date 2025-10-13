<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use App\Enum\CurrencyCodeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),
            AssociationField::new('owner'),
            AssociationField::new('engagement'),
            MoneyField::new('amountMinor')->setStoredAsCents(true)->setCurrency(CurrencyCodeEnum::CZK->value),
            DateTimeField::new('createdAt'),
        ];
    }
}
