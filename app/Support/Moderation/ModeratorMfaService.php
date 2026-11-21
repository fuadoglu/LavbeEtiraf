<?php

namespace App\Support\Moderation;

use App\Models\ModeratorMfa;
use App\Models\ModeratorRecoveryCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ModeratorMfaService
{
    public function newSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    public function provisioningUri(string $secret, string $email): string
    {
        $label = rawurlencode(config('app.name').':'.$email);
        $query = http_build_query([
            'secret' => $secret,
            'issuer' => config('app.name'),
            'algorithm' => 'SHA1',
            'digits' => 6,
            'period' => config('moderation.totp_period_seconds'),
        ], '', '&', PHP_QUERY_RFC3986);

        return 'otpauth://totp/'.$label.'?'.$query;
    }

    public function encryptSecret(string $secret): string
    {
        $nonce = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($secret, 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, $nonce, $tag, 'lavbe-moderator-mfa-v1');
        if (! is_string($ciphertext)) {
            throw new RuntimeException('Could not encrypt moderator MFA material.');
        }

        return base64_encode($nonce.$tag.$ciphertext);
    }

    public function decryptSecret(string $payload): string
    {
        $decoded = base64_decode($payload, true);
        if (! is_string($decoded) || strlen($decoded) < 29) {
            throw new RuntimeException('Invalid moderator MFA ciphertext.');
        }
        $secret = openssl_decrypt(substr($decoded, 28), 'aes-256-gcm', $this->encryptionKey(), OPENSSL_RAW_DATA, substr($decoded, 0, 12), substr($decoded, 12, 16), 'lavbe-moderator-mfa-v1');
        if (! is_string($secret)) {
            throw new RuntimeException('Could not decrypt moderator MFA material.');
        }

        return $secret;
    }

    public function matchingCounter(string $secret, string $code, ?int $lastCounter = null, ?int $timestamp = null): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $period = (int) config('moderation.totp_period_seconds');
        $current = intdiv($timestamp ?? time(), $period);
        $window = (int) config('moderation.totp_window');
        for ($offset = -$window; $offset <= $window; $offset++) {
            $counter = $current + $offset;
            if ($counter < 0 || ($lastCounter !== null && $counter <= $lastCounter)) {
                continue;
            }
            if (hash_equals($this->codeForCounter($secret, $counter), $code)) {
                return $counter;
            }
        }

        return null;
    }

    public function codeForCounter(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $message = pack('N2', intdiv($counter, 4_294_967_296), $counter % 4_294_967_296);
        $hash = hash_hmac('sha1', $message, $key, true);
        $offset = ord($hash[19]) & 0x0F;
        $binary = ((ord($hash[$offset]) & 0x7F) << 24)
            | ((ord($hash[$offset + 1]) & 0xFF) << 16)
            | ((ord($hash[$offset + 2]) & 0xFF) << 8)
            | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($binary % 1_000_000), 6, '0', STR_PAD_LEFT);
    }

    public function recoveryCodeHash(string $code): string
    {
        return hash_hmac('sha256', 'moderator-recovery|'.$code, (string) config('app.key'));
    }

    /** @return list<string> */
    public function confirmEnrollment(User $user, string $code): array
    {
        return DB::transaction(function () use ($user, $code): array {
            $mfa = ModeratorMfa::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $mfa?->pending_secret_ciphertext || $mfa->confirmed_at) {
                return [];
            }
            $secret = $this->decryptSecret($mfa->pending_secret_ciphertext);
            $counter = $this->matchingCounter($secret, $code, $mfa->last_counter);
            if ($counter === null) {
                return [];
            }

            $codes = [];
            for ($index = 0; $index < 10; $index++) {
                $codes[] = rtrim(strtr(base64_encode(random_bytes(20)), '+/', '-_'), '=');
            }
            ModeratorRecoveryCode::query()->where('user_id', $user->id)->delete();
            foreach ($codes as $recoveryCode) {
                ModeratorRecoveryCode::query()->create([
                    'user_id' => $user->id,
                    'code_hash' => $this->recoveryCodeHash($recoveryCode),
                ]);
            }
            $mfa->forceFill([
                'secret_ciphertext' => $mfa->pending_secret_ciphertext,
                'pending_secret_ciphertext' => null,
                'confirmed_at' => now(),
                'last_counter' => $counter,
            ])->save();

            return $codes;
        });
    }

    public function verify(User $user, string $code): bool
    {
        return DB::transaction(function () use ($user, $code): bool {
            $mfa = ModeratorMfa::query()->where('user_id', $user->id)->lockForUpdate()->first();
            if (! $mfa?->confirmed_at || ! $mfa->secret_ciphertext) {
                return false;
            }
            $secret = $this->decryptSecret($mfa->secret_ciphertext);
            $counter = $this->matchingCounter($secret, $code, $mfa->last_counter);
            if ($counter !== null) {
                $mfa->forceFill(['last_counter' => $counter])->save();

                return true;
            }

            $hash = $this->recoveryCodeHash($code);
            $recovery = ModeratorRecoveryCode::query()
                ->where('user_id', $user->id)
                ->where('code_hash', $hash)
                ->whereNull('used_at')
                ->lockForUpdate()
                ->first();
            if (! $recovery) {
                return false;
            }
            $recovery->forceFill(['used_at' => now()])->save();

            return true;
        });
    }

    private function encryptionKey(): string
    {
        $configured = (string) config('moderation.mfa_key');
        $key = base64_decode($configured, true);
        if (! is_string($key) || strlen($key) !== 32) {
            throw new RuntimeException('MODERATOR_MFA_KEY must be a base64-encoded 32-byte key.');
        }

        return $key;
    }

    private function base32Encode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split($value) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $encoded = '';
        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0'))];
        }

        return $encoded;
    }

    private function base32Decode(string $value): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';
        foreach (str_split(strtoupper(rtrim($value, '='))) as $character) {
            $position = strpos($alphabet, $character);
            if ($position === false) {
                throw new RuntimeException('Invalid base32 moderator MFA secret.');
            }
            $bits .= str_pad(decbin($position), 5, '0', STR_PAD_LEFT);
        }
        $binary = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $binary .= chr(bindec($chunk));
            }
        }

        return $binary;
    }
}
