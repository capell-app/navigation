<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Forms;

class Turnstile
{
    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
    ) {}

    public function renderWidget(): string
    {
        return '<div class="cf-turnstile" data-sitekey="' . htmlspecialchars($this->siteKey, ENT_QUOTES) . '" data-callback="onTurnstileSuccess"></div>'
            . '<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>';
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function verificationUrl(): string
    {
        return 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    }

    /**
     * @return array{secret: string, response: string}
     */
    public function verificationPayload(string $token): array
    {
        return [
            'secret' => $this->secretKey,
            'response' => $token,
        ];
    }
}
