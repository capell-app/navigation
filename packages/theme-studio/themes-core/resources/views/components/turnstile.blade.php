@props(['siteKey'])
<div
    class="cf-turnstile"
    data-sitekey="{{ $siteKey }}"
    data-callback="onTurnstileSuccess"
></div>
<script
    src="https://challenges.cloudflare.com/turnstile/v0/api.js"
    async
    defer
></script>
