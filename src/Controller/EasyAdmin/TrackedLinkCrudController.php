<?php

namespace App\Controller\EasyAdmin;

use App\Entity\TrackedLink;
use App\Enum\TrackedLinkSourceEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class TrackedLinkCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TrackedLink::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Tracked Link')
            ->setEntityLabelInPlural('Tracked Links')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('code')
                ->setHelp('Unique short code for this tracked link'),
            UrlField::new('originalUrl')
                ->setLabel('Target URL')
                ->setHelp('The destination URL where this link redirects'),
            AssociationField::new('advertisingCampaign')
                ->setLabel('Campaign')
                ->setRequired(false),
            ChoiceField::new('source')
                ->setChoices(array_combine(
                    array_map(fn(TrackedLinkSourceEnum $enum) => $enum->value, TrackedLinkSourceEnum::cases()),
                    TrackedLinkSourceEnum::cases()
                ))
                ->setRequired(false)
                ->setHelp('Source type for this tracked link'),
            IntegerField::new('clickCount')
                ->setLabel('Clicks')
                ->onlyOnIndex(),
            BooleanField::new('isActive')
                ->setLabel('Active'),
            CodeEditorField::new('metadata')
                ->hideOnIndex()
                ->setHelp('Additional tracking metadata (JSON format)'),
            DateTimeField::new('createdAt')
                ->hideOnForm(),
            DateTimeField::new('updatedAt')
                ->hideOnForm()
                ->hideOnIndex(),
            DateTimeField::new('lastClickedAt')
                ->setLabel('Last Click')
                ->hideOnForm(),
        ];
    }
}
