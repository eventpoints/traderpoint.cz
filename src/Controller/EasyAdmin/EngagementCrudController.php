<?php

namespace App\Controller\EasyAdmin;

use App\Entity\Engagement;
use App\Enum\EngagementStatusEnum;
use App\Enum\FlashEnum;
use App\Form\Type\MapLocationType;
use App\Service\EngagementWorkflowService;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractCrudController<Engagement>
 */
class EngagementCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly EngagementWorkflowService $workflowService,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Engagement::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->addFormTheme('admin/form/map_location.html.twig');
    }

    public function configureActions(Actions $actions): Actions
    {
        $approve = Action::new('approve', 'Approve', 'fa fa-check')
            ->linkToCrudAction('approveEngagement')
            ->displayIf(
                static fn (Engagement $engagement): bool =>
                $engagement->getStatus() === EngagementStatusEnum::UNDER_ADMIN_REVIEW
            )
            ->setCssClass('btn btn-success');

        $reject = Action::new('reject', 'Reject', 'fa fa-times')
            ->linkToCrudAction('rejectEngagement')
            ->displayIf(
                static fn (Engagement $engagement): bool =>
                $engagement->getStatus() === EngagementStatusEnum::UNDER_ADMIN_REVIEW
            )
            ->setCssClass('btn btn-danger');

        return $actions
            ->add(Crud::PAGE_DETAIL, $approve)
            ->add(Crud::PAGE_DETAIL, $reject)
            ->add(Crud::PAGE_INDEX, $approve)
            ->add(Crud::PAGE_INDEX, $reject);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            TextField::new('title'),
            TextField::new('address'),
            TextareaField::new('description'),
            Field::new('status', 'Status')
                ->formatValue(static fn ($value) => $value instanceof EngagementStatusEnum ? $value->value : ''),
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

    public function approveEngagement(AdminContext $context): RedirectResponse
    {
        /** @var Engagement $engagement */
        $engagement = $context->getEntity()->getInstance();

        try {
            $this->workflowService->approve($engagement);
            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('admin.engagement.approved_successfully')
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('admin.engagement.cannot_approve', [
                    'error' => $e->getMessage(),
                ])
            );
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($engagement->getId())
            ->generateUrl();

        return $this->redirect($url);
    }

    public function rejectEngagement(AdminContext $context): RedirectResponse
    {
        /** @var Engagement $engagement */
        $engagement = $context->getEntity()->getInstance();

        try {
            $this->workflowService->reject($engagement, 'Rejected by admin');
            $this->addFlash(
                FlashEnum::SUCCESS->value,
                $this->translator->trans('admin.engagement.rejected_successfully')
            );
        } catch (\LogicException $e) {
            $this->addFlash(
                FlashEnum::ERROR->value,
                $this->translator->trans('admin.engagement.cannot_reject', [
                    'error' => $e->getMessage(),
                ])
            );
        }

        $url = $this->adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($engagement->getId())
            ->generateUrl();

        return $this->redirect($url);
    }
}
