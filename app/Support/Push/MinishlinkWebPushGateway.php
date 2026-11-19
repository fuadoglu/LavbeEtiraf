<?php

namespace App\Support\Push;

use Minishlink\WebPush\MessageSentReport;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Psr\Log\NullLogger;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

class MinishlinkWebPushGateway implements WebPushGateway
{
    public function __construct(private readonly PushEndpointPolicy $endpointPolicy) {}

    public function send(array $items): array
    {
        if ($items === []) {
            return [];
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ], [
            'TTL' => 3600,
            'urgency' => 'normal',
            'topic' => 'lavbe-activity',
            'batchSize' => max(1, count($items)),
        ], new Psr18Client(HttpClient::create([
            'timeout' => 5,
            'max_redirects' => 0,
            'max_duration' => 6,
        ])), logger: new NullLogger);

        $byEndpoint = [];
        $results = [];
        foreach ($items as $item) {
            $subscription = $item['subscription'];
            $endpoint = (string) $subscription->endpoint;
            if (! $this->endpointPolicy->allows($endpoint)) {
                $results[$item['delivery_id']] = ['success' => false, 'expired' => false, 'status' => 400];

                continue;
            }

            $webPush->queueNotification(Subscription::create([
                'endpoint' => $endpoint,
                'keys' => ['p256dh' => $subscription->p256dh, 'auth' => $subscription->auth],
            ]), json_encode($item['payload'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $byEndpoint[$endpoint] = $item['delivery_id'];
        }

        /** @var MessageSentReport $report */
        foreach ($webPush->flush(max(1, count($items))) as $report) {
            $deliveryId = $byEndpoint[$report->getEndpoint()] ?? null;
            if (! $deliveryId) {
                continue;
            }
            $results[$deliveryId] = [
                'success' => $report->isSuccess(),
                'expired' => $report->isSubscriptionExpired(),
                'status' => $report->getResponse()?->getStatusCode(),
            ];
        }

        return $results;
    }
}
