<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Analytics;

/**
 * Thin wrapper that emits GA4 initialisation + event JavaScript snippets.
 *
 * The class renders strings only — it makes no network calls and expects the
 * host theme to echo the returned markup into a `<script>` block.
 */
class GoogleAnalytics4 implements AnalyticsProvider
{
    private const JSON_SCRIPT_FLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR;

    public function __construct(
        private readonly string $measurementId,
        private readonly bool $anonymiseIp = true,
        private readonly bool $enabled = true,
    ) {}

    public function isEnabled(): bool
    {
        return $this->enabled && $this->measurementId !== '';
    }

    public function measurementId(): string
    {
        return $this->measurementId;
    }

    /**
     * Returns the `<script>` markup that bootstraps GA4.
     */
    public function renderInitScript(): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $id = $this->escapeJs($this->measurementId);
        $config = json_encode([
            'anonymize_ip' => $this->anonymiseIp,
            'send_page_view' => true,
        ], self::JSON_SCRIPT_FLAGS);

        return <<<HTML
<script async src="https://www.googletagmanager.com/gtag/js?id={$id}"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '{$id}', {$config});
</script>
HTML;
    }

    /**
     * Build a gtag event call as a JS snippet.
     *
     * @param  array<string, mixed>  $params
     */
    public function track(string $event, array $params = []): string
    {
        if (! $this->isEnabled()) {
            return '';
        }

        $payload = json_encode($params, self::JSON_SCRIPT_FLAGS);

        return sprintf("gtag('event', %s, %s);", json_encode($event, self::JSON_SCRIPT_FLAGS), $payload);
    }

    public function formSubmission(string $formName): string
    {
        return $this->track('form_submission', ['form_name' => $formName]);
    }

    public function ctaClick(string $label, string $location): string
    {
        return $this->track('cta_click', ['label' => $label, 'location' => $location]);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function purchase(array $items, ?string $transactionId = null, ?float $value = null, string $currency = 'USD'): string
    {
        return $this->track('purchase', array_filter([
            'transaction_id' => $transactionId,
            'value' => $value,
            'currency' => $currency,
            'items' => $items,
        ], static fn (mixed $value): bool => $value !== null));
    }

    private function escapeJs(string $value): string
    {
        return preg_replace('/[^A-Za-z0-9_\-]/', '', $value) ?? '';
    }
}
