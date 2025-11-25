<?php
declare(strict_types=1);

namespace App\Data;

use App\ViewModel\FaqModelView;

final readonly class FaqData
{
    /**
     * @return list<FaqModelView>
     */
    public static function getItems(): array
    {
        return [
            new FaqModelView(
                'faq.q.what-is-traderpoint',
                'faq.a.what-is-traderpoint',
            ),
            new FaqModelView(
                'faq.q.how-it-works-clients',
                'faq.a.how-it-works-clients',
            ),
            new FaqModelView(
                'faq.q.how-it-works-traders',
                'faq.a.how-it-works-traders',
            ),
            new FaqModelView(
                'faq.q.trader-pricing',
                'faq.a.trader-pricing',
                ['{trial_days}' => 90],
            ),
            new FaqModelView(
                'faq.q.client-pricing',
                'faq.a.client-pricing',
            ),
            new FaqModelView(
                'faq.q.who-is-the-contract-with',
                'faq.a.who-is-the-contract-with',
            ),
            new FaqModelView(
                'faq.q.where-available',
                'faq.a.where-available',
                ['{country}' => 'Czech Republic'],
            ),
            new FaqModelView(
                'faq.q.languages',
                'faq.a.languages',
            ),
            new FaqModelView(
                'faq.q.trader-verification',
                'faq.a.trader-verification',
            ),
            new FaqModelView(
                'faq.q.payments',
                'faq.a.payments',
            ),
            new FaqModelView(
                'faq.q.manage-subscription',
                'faq.a.manage-subscription',
            ),
            new FaqModelView(
                'faq.q.report-problem',
                'faq.a.report-problem',
                ['{support_email}' => 'support@traderpoint.cz'],
            ),
            new FaqModelView(
                'faq.q.mobile',
                'faq.a.mobile',
            ),
        ];
    }
}
