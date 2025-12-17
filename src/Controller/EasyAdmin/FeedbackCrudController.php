<?php

namespace App\Controller\EasyAdmin;

use App\Entity\Feedback;
use App\Enum\FeedbackStatusEnum;
use App\Enum\FeedbackTypeEnum;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class FeedbackCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Feedback::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Feedback')
            ->setEntityLabelInPlural('Feedback')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextareaField::new('content')
                ->setMaxLength(5000)
                ->hideOnIndex(),
            TextField::new('content')
                ->setMaxLength(100)
                ->onlyOnIndex()
                ->setLabel('Content Preview'),
            ChoiceField::new('category')
                ->setChoices([
                    'Bug Report' => FeedbackTypeEnum::BUG_REPORT,
                    'Feature Request' => FeedbackTypeEnum::FEATURE_REQUEST,
                    'General Feedback' => FeedbackTypeEnum::GENERAL,
                    'Complaint' => FeedbackTypeEnum::COMPLAINT,
                    'Praise' => FeedbackTypeEnum::PRAISE,
                    'Other' => FeedbackTypeEnum::OTHER,
                ])
                ->allowMultipleChoices(false),
            ChoiceField::new('status')
                ->setChoices([
                    'Pending' => FeedbackStatusEnum::PENDING,
                    'In Review' => FeedbackStatusEnum::IN_REVIEW,
                    'Resolved' => FeedbackStatusEnum::RESOLVED,
                    'Closed' => FeedbackStatusEnum::CLOSED,
                ])
                ->allowMultipleChoices(false),
            TextField::new('page')->hideOnIndex(),
            AssociationField::new('owner'),
            TextareaField::new('adminNotes')
                ->hideOnIndex()
                ->setHelp('Internal notes visible only to admins'),
            DateTimeField::new('createdAt')->hideOnForm(),
            DateTimeField::new('updatedAt')->hideOnForm(),
        ];
    }
}
