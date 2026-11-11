<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Session\DatabaseSessionHandler;

/**
 * the stock database handler rewrites the session row on every request, even when nothing in the
 * session changed. each rewrite spends the database's monthly allowance. this one skips the
 * write when the session is unchanged and its row was touched recently, so a person reading
 * several pages costs one write every few minutes instead of one per page.
 *
 * a signed-out visitor who is only reading gets no row at all. their session holds nothing but the
 * framework's own bookkeeping, so there is nothing to lose by keeping it in memory. the row appears
 * with the first request that needs it: a page that prints a form token, a flash message, the start
 * of a sign-in or anything else put in the session
 */
class LazyDatabaseSessionHandler extends DatabaseSessionHandler
{
    /** how stale last_activity may get before an unchanged session is written anyway */
    public const REFRESH_SECONDS = 300;

    /** keys the framework keeps for itself; a session holding nothing else has nothing worth storing */
    private const BOOKKEEPING = ['_token', '_previous', '_flash'];

    /** what the row held when it was read, to compare against at write time */
    private ?array $stored = null;

    /** true once the response is known to carry no form token, so a later form post cannot depend on this session */
    private bool $tokenUnsent = false;

    public function read($sessionId): string|false
    {
        // the same lookup as the parent's, done once, with the row kept for the comparison in write()
        $this->stored = null;
        $this->tokenUnsent = false;
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

    /** whether this visitor already has a stored session, so a form token printed now can be checked later */
    public function hasRow(): bool
    {
        return $this->stored !== null;
    }

    /**
     * called once the response is built and the token is nowhere in it. without this call the session is
     * always written, so a request that never reaches the check keeps the old behaviour
     */
    public function markTokenUnsent(): void
    {
        $this->tokenUnsent = true;
    }

    public function write($sessionId, $data): bool
    {
        // no row yet, no token sent out and nothing real to keep: the visitor is only reading
        if ($this->stored === null && $this->tokenUnsent && $this->holdsNothing($data)) {
            return true;
        }

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

    private function holdsNothing(string $data): bool
    {
        $decoded = $this->decoded($data);
        if (! is_array($decoded) || array_diff(array_keys($decoded), self::BOOKKEEPING) !== []) {
            return false;
        }
        // a message flashed for the next page has to survive the redirect
        if (array_filter((array) ($decoded['_flash'] ?? [])) !== []) {
            return false;
        }

        // a signed-in visitor always keeps a row: the devices list and the security emails read it
        return ($this->getDefaultPayload($data)['user_id'] ?? null) === null;
    }

    /**
     * the last page visited is saved on every page view, which would make every session look changed.
     * redirects back use the browser's Referer header first, so a few minutes' stale value is harmless
     */
    private function withoutPreviousUrl(string $data): mixed
    {
        $decoded = $this->decoded($data);
        if (is_array($decoded)) {
            unset($decoded['_previous']);
        }

        return $decoded;
    }

    /** the session as an array. text that cannot be read comes back as it was */
    private function decoded(string $data): mixed
    {
        // an encrypted session gets a fresh ciphertext on every save, so the plain text is compared.
        // it is opened the way the framework's encrypted store opens it, which wraps the text as a php string
        if (config('session.encrypt')) {
            try {
                $plain = $this->container->make('encrypter')->decrypt($data);
            } catch (DecryptException) {
                return $data;
            }
            if (! is_string($plain)) {
                return $data;
            }
            $data = $plain;
        }
        $decoded = json_decode($data, true);

        return is_array($decoded) ? $decoded : $data;
    }
}
