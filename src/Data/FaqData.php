<?php

declare(strict_types=1);

namespace App\Data;

use App\ViewModel\FaqModelView;

final readonly class FaqData
{
    /**
     * @return list<FaqModelView>
     */
    public static function getClientFaqs(): array
    {
        return [
            // General: what is this thing?
            new FaqModelView(
                'faq.q.what-is-traderpoint',
                'faq.a.what-is-traderpoint',
            ),

            // How it works specifically for clients
            new FaqModelView(
                'faq.q.how-it-works-clients',
                'faq.a.how-it-works-clients',
            ),

            // What does it cost me as a client?
            new FaqModelView(
                'faq.q.client-pricing',
                'faq.a.client-pricing',
            ),

            // Who am I actually making a contract with?
            new FaqModelView(
                'faq.q.who-is-the-contract-with',
                'faq.a.who-is-the-contract-with',
            ),

            // Where is the service available?
            new FaqModelView(
                'faq.q.where-available',
                'faq.a.where-available',
                [
                    '{country}' => 'Czech Republic',
                ],
            ),

            // In which languages can I use the platform?
            new FaqModelView(
                'faq.q.languages',
                'faq.a.languages',
            ),

            // Are traders verified / how do you check them?
            new FaqModelView(
                'faq.q.trader-verification',
                'faq.a.trader-verification',
            ),

            // How do payments work between me and the trader?
            new FaqModelView(
                'faq.q.payments',
                'faq.a.payments',
            ),

            // What do I do if something goes wrong?
            new FaqModelView(
                'faq.q.report-problem',
                'faq.a.report-problem',
                [
                    '{support_email}' => 'support@traderpoint.cz',
                ],
            ),

            // Can I use Trader Point on my phone / tablet?
            new FaqModelView(
                'faq.q.mobile',
                'faq.a.mobile',
            ),
        ];
    }

    /**
     * Trader-focused FAQs (for trader landing / dashboard pages).
     *
     * @return list<FaqModelView>
     */
    public static function getTradesmenFaqs(): array
    {
        return [
            new FaqModelView(
                'faq.q.how-it-works-traders',
                'faq.a.how-it-works-traders',
            ),
            new FaqModelView(
                'faq.q.trader-pricing',
                'faq.a.trader-pricing',
                [
                    '{trial_days}' => 90,
                ],
            ),
            new FaqModelView(
                'faq.q.trader-credits',
                'faq.a.trader-credits',
            ),
            new FaqModelView(
                'faq.q.trader-per-lead',
                'faq.a.trader-per-lead',
            ),
            new FaqModelView(
                'faq.q.who-is-the-contract-with',
                'faq.a.who-is-the-contract-with',
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
                'faq.q.where-available',
                'faq.a.where-available',
                [
                    '{country}' => 'Czech Republic',
                ],
            ),
            new FaqModelView(
                'faq.q.languages',
                'faq.a.languages',
            ),
            new FaqModelView(
                'faq.q.manage-subscription',
                'faq.a.manage-subscription',
            ),
            new FaqModelView(
                'faq.q.report-problem',
                'faq.a.report-problem',
                [
                    '{support_email}' => 'support@traderpoint.cz',
                ],
            ),
            new FaqModelView(
                'faq.q.mobile',
                'faq.a.mobile',
            ),
        ];
    }
}
