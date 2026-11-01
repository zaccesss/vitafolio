<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Session\DatabaseSessionHandler;

/**
 * the stock database handler rewrites the session row on every request, even when nothing in the
 * session changed. each rewrite spends the database's monthly allowance. this one skips the
 * write when the session is unchanged and its row was touched recently, so a person reading
 * several pages costs one write every few minutes instead of one per page
 */
class LazyDatabaseSessionHandler extends DatabaseSessionHandler
{
    /** how stale last_activity may get before an unchanged session is written anyway */
    public const REFRESH_SECONDS = 300;

    /** what the row held when it was read, to compare against at write time */
    private ?array $stored = null;

    public function read($sessionId): string|false
    {
        // the same lookup as the parent's, done once, with the row kept for the comparison in write()
        $this->stored = null;
        $session = (object) $this->getQuery()->find($sessionId);

        if ($this->expired($session)) {
            $this->exists = true;

            return '';
        }

        if (isset($session->payload)) {
            $this->exists = true;
            $this->stored = [
                'id' => $sessionId,
                'data' => base64_decode($session->payload),
                'last_activity' => (int) $session->last_activity,
                'user_id' => $session->user_id ?? null,
                'ip_address' => $session->ip_address ?? null,
                'user_agent' => $session->user_agent ?? null,
            ];

            return $this->stored['data'];
        }

        return '';
    }

    public function write($sessionId, $data): bool
    {
        // a new id (sign-in regenerates it) has no row yet, so it is always written
        if ($this->stored !== null && $this->stored['id'] === $sessionId && $this->unchanged($data)) {
            return true;
        }

        $written = parent::write($sessionId, $data);
        $this->stored = null;

        return $written;
    }

    public function destroy($sessionId): bool
    {
        $this->stored = null;

        return parent::destroy($sessionId);
    }

    private function unchanged(string $data): bool
    {
        if ($this->currentTime() - $this->stored['last_activity'] >= self::REFRESH_SECONDS) {
            return false;
        }

        // the user, address and browser feed the signed-in devices list, so any change there is written
        $payload = $this->getDefaultPayload($data);
        foreach (['user_id', 'ip_address', 'user_agent'] as $column) {
            if (array_key_exists($column, $payload) && $payload[$column] != $this->stored[$column]) {
                return false;
            }
        }

        return $this->withoutPreviousUrl($data) === $this->withoutPreviousUrl($this->stored['data']);
    }

    /**
     * the last page visited is saved on every page view, which would make every session look changed.
     * redirects back use the browser's Referer header first, so a few minutes' stale value is harmless
     */
    private function withoutPreviousUrl(string $data): mixed
    {
        // an encrypted session gets a fresh ciphertext on every save, so the plain text is compared
        if (config('session.encrypt')) {
            try {
                $data = (string) $this->container->make('encrypter')->decrypt($data, false);
            } catch (DecryptException) {
                return $data;
            }
        }
        $decoded = json_decode($data, true);
        if (! is_array($decoded)) {
            return $data;
        }
        unset($decoded['_previous']);

        return $decoded;
    }
}
