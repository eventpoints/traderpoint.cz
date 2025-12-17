<?php

namespace App\Controller\EasyAdmin;

use App\Entity\TrackedLinkClick;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

/**
 * @extends AbstractCrudController<TrackedLinkClick>
 */
class TrackedLinkClickCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return TrackedLinkClick::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Link Click')
            ->setEntityLabelInPlural('Link Clicks')
            ->setDefaultSort([
                'clickedAt' => 'DESC',
            ])
            ->setSearchFields(['ipAddress', 'userAgent', 'referer', 'country', 'city', 'browser', 'device', 'platform']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnDetail(),
            AssociationField::new('trackedLink')
                ->setLabel('Tracked Link'),
            DateTimeField::new('clickedAt')
                ->setLabel('Clicked At'),
            TextField::new('ipAddress')
                ->setLabel('IP Address')
                ->hideOnIndex(),
            TextField::new('country')
                ->hideOnIndex(),
            TextField::new('city')
                ->hideOnIndex(),
            TextField::new('device')
                ->setLabel('Device'),
            TextField::new('browser')
                ->setLabel('Browser')
                ->hideOnIndex(),
            TextField::new('platform')
                ->setLabel('Platform')
                ->hideOnIndex(),
            TextareaField::new('userAgent')
                ->setLabel('User Agent')
                ->onlyOnDetail(),
            TextField::new('referer')
                ->setLabel('Referer')
                ->onlyOnDetail(),
            CodeEditorField::new('metadata')
                ->hideOnIndex()
                ->onlyOnDetail()
                ->setHelp('Additional tracking metadata'),
        ];
    }
}
