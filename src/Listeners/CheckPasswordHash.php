<?php

namespace GhanaCompliance\Act843SDK\Listeners;

use GhanaCompliance\Act843SDK\Services\Security\ComplianceEngine;

class CheckPasswordHash
{
    public function handle($user): void
    {
        $password = $user->password ?? null;
        if ($password === null || $password === '') {
            return;
        }

        if ($this->isWeakHash($password)) {
            ComplianceEngine::log([
                'type' => 'PASSWORD_WEAK_HASH',
                'score' => 70,
                'severity' => 'HIGH',
                'attempts' => 0,
                'meta' => [
                    'user_id' => $user->id,
                    'ip' => request()->ip(),
                ],
                'recommendation' => 'The stored password hash is weak (plain text, MD5, SHA1, or unsalted). Re‑hash using bcrypt/Argon2 immediately.',
            ]);
        }
    }

    protected function isWeakHash(string $hash): bool
    {
        if (preg_match('/^\$2[aby]\$\d+\$/', $hash)) return false; // bcrypt
        if (str_starts_with($hash, '$argon2')) return false;       // Argon2
        if (preg_match('/^[a-f0-9]{32}$/i', $hash)) return true;  // MD5
        if (preg_match('/^[a-f0-9]{40}$/i', $hash)) return true;  // SHA1
        return true; // includes plain text
    }
}
