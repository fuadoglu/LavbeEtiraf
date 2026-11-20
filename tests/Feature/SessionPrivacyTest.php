<?php

namespace Tests\Feature;

use Illuminate\Session\ArraySessionHandler;
use Illuminate\Session\EncryptedStore;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SessionPrivacyTest extends TestCase
{
    public function test_session_payload_is_encrypted_and_can_restore_a_flushed_draft(): void
    {
        config(['session.encrypt' => true]);
        $handler = new ArraySessionHandler(120);
        $store = new EncryptedStore('lavbe_test', $handler, app('encrypter'));
        $store->start();
        $store->flash('_old_input', ['body' => 'Synthetic private draft']);
        $store->save();
        $raw = $handler->read($store->getId());
        $this->assertNotSame('', $raw);
        $this->assertStringNotContainsString('Synthetic private draft', $raw);
        $this->assertStringContainsString('Synthetic private draft', Crypt::decryptString($raw));
        $restored = new EncryptedStore('lavbe_test', $handler, app('encrypter'), $store->getId());
        $restored->start();
        $this->assertSame('Synthetic private draft', $restored->get('_old_input')['body']);
    }
}
