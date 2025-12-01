<?php

declare(strict_types=1);

namespace App\Form\Form;

use App\Entity\User;
use App\Entity\UserNotificationSettings;
use App\Form\Type\SwitchType;
use LogicException;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserNotificationSettingsType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var User|null $currentUser */
        $currentUser = $options['current_user'] ?? null;

        if (! $currentUser instanceof User) {
            throw new LogicException('UserNotificationSettingsType requires "current_user" option to be a User.');
        }

        if ($currentUser->isTrader()) {
            // TRADER – new matching jobs
            $builder
                ->add('isTraderReceiveEmailOnMatchingJob', SwitchType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('trader.new_matching_job.email'),
                ])
                ->add('isTraderReceiveSmsOnMatchingJob', SwitchType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('trader.new_matching_job.sms'),
                ]);
        } else {
            // CLIENT – quotes on my job
            $builder
                ->add('isClientReceiveEmailOnQuote', SwitchType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('client.new_quote.email'),
                ])
                ->add('isClientReceiveSmsOnQuote', SwitchType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('client.new_quote.sms'),
                ])
                ->add('isClientReceiveEmailOnEngagementMessage', SwitchType::class, [
                    'required' => false,
                    'label' => $this->translator->trans('job.new_message.email'),
                ]);
        }

        // SHARED – new messages
        $builder
            // MARKETING / NEWS
            ->add('isReceiveMarketingEmail', SwitchType::class, [
                'required' => false,
                'label' => $this->translator->trans('marketing.email'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserNotificationSettings::class,
            'current_user' => null,
            'translation_domain' => 'notifications',
        ]);

        $resolver->setAllowedTypes('current_user', ['null', User::class]);
    }
}
