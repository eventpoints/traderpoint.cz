<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\EngagementIssue;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class IssueMediationAIService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $anthropicApiKey,
    ) {
    }

    public function mediateIssue(EngagementIssue $issue): string
    {
        $engagement = $issue->getEngagement();
        $issueType = $issue->getType()->value;
        $clientEvidence = $issue->getClientEvidence();
        $traderEvidence = $issue->getTraderEvidence();

        $prompt = $this->buildMediationPrompt(
            $issueType,
            $engagement->getTitle(),
            $clientEvidence,
            $traderEvidence
        );

        try {
            $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $this->anthropicApiKey,
                    'anthropic-version' => '2023-06-01',
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
            ]);

            $data = $response->toArray();

            return $data['content'][0]['text'] ?? 'Unable to generate mediation response.';
        } catch (\Exception) {
            // Log error and return fallback message
            return 'We are currently unable to process this mediation. Please contact support for manual assistance.';
        }
    }

    private function buildMediationPrompt(
        string $issueType,
        string $engagementTitle,
        string $clientEvidence,
        string $traderEvidence
    ): string {
        return <<<PROMPT
You are an impartial mediator for a tradesperson marketplace platform. Your role is to analyze disputes between clients and tradespeople and provide fair, balanced recommendations.

**Engagement**: {$engagementTitle}
**Issue Type**: {$issueType}

**Client's Statement**:
{$clientEvidence}

**Trader's Statement**:
{$traderEvidence}

Please analyze both statements and provide:

1. **Summary**: A brief, neutral summary of the dispute
2. **Analysis**: Key points from each party and any inconsistencies or agreements
3. **Recommendation**: A fair resolution that considers both parties' perspectives
4. **Next Steps**: Specific actionable steps for resolving this issue

Be objective, professional, and focus on finding a fair solution. If more information is needed, state that clearly.

Format your response in clear sections as outlined above.
PROMPT;
    }
}
