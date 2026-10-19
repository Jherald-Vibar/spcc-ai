<?php

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Email one-time codes for registration.
 *
 * The code itself is never stored, only an HMAC of (email + code), so a leaked
 * cache/database row can't be turned back into a valid code.
 */
class OtpService
{
    public const TTL_MINUTES = 10;     // how long a code stays valid
    public const RESEND_SECONDS = 30;  // cooldown before "Resend code" unlocks
    public const MAX_ATTEMPTS = 5;     // wrong guesses before the code is burned

    public const OK = 'ok';
    public const EXPIRED = 'expired';
    public const INVALID = 'invalid';
    public const LOCKED = 'locked';

    /**
     * Generate a new code, store its hash and email it.
     *
     * @throws Throwable when the mail cannot be sent (the stored code is discarded first)
     */
    public function issue(string $email, string $name): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $now = time();
        $ttl = self::TTL_MINUTES * 60;

        Cache::put($this->key($email), [
            'hash' => $this->hash($email, $code),
            'attempts' => 0,
            'sent_at' => $now,
            'expires_at' => $now + $ttl,
        ], $ttl);

        try {
            Mail::to($email)->send(new OtpMail($code, $name, self::TTL_MINUTES));
        } catch (Throwable $e) {
            Cache::forget($this->key($email));
            throw $e;
        }
    }

    /**
     * Check a submitted code. Returns one of the OK / EXPIRED / INVALID / LOCKED constants.
     */
    public function verify(string $email, string $code): string
    {
        $key = $this->key($email);
        $entry = Cache::get($key);

        if (! is_array($entry)) {
            return self::EXPIRED;
        }

        if (hash_equals($entry['hash'], $this->hash($email, $code))) {
            return self::OK;
        }

        $entry['attempts']++;

        if ($entry['attempts'] >= self::MAX_ATTEMPTS) {
            Cache::forget($key);

            return self::LOCKED;
        }

        // keep the original expiry; a wrong guess must not extend the code's life
        Cache::put($key, $entry, max(1, $entry['expires_at'] - time()));

        return self::INVALID;
    }

    /**
     * Seconds left before another code may be requested (0 = allowed now).
     */
    public function cooldownRemaining(string $email): int
    {
        $entry = Cache::get($this->key($email));

        if (! is_array($entry)) {
            return 0;
        }

        return max(0, self::RESEND_SECONDS - (time() - $entry['sent_at']));
    }

    public function forget(string $email): void
    {
        Cache::forget($this->key($email));
    }

    private function key(string $email): string
    {
        return 'register-otp:' . strtolower($email);
    }

    private function hash(string $email, string $code): string
    {
        return hash_hmac('sha256', strtolower($email) . '|' . $code, (string) config('app.key'));
    }
}
