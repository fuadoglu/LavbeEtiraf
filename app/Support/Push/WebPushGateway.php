<?php

namespace App\Support\Push;

use App\Models\PushSubscription;

interface WebPushGateway
{
    /** @param list<array{delivery_id: int, subscription: PushSubscription, payload: array<string, string>}> $items
     * @return array<int, array{success: bool, expired: bool, status: int|null}>
     */
    public function send(array $items): array;
}
