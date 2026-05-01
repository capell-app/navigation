<?php

declare(strict_types=1);

use Capell\Themes\Core\Middleware\PreviewMiddleware;
use Capell\Themes\Core\Preview\PreviewMode;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('preview middleware validates request paths against leading slash tokens', function (): void {
    $preview = new PreviewMode(secretKey: 'my-app-key');
    $token = $preview->generateToken('/page/my-draft', expiresInMinutes: 60);
    $request = Request::create('/page/my-draft', Symfony\Component\HttpFoundation\Request::METHOD_GET, [
        $preview->tokenParam() => $token,
    ]);

    $response = (new PreviewMiddleware($preview))->handle(
        $request,
        static fn (Request $handledRequest): Response => new Response('ok'),
    );

    expect($response->getContent())->toBe('ok');
    expect(view()->getShared()['isPreviewMode'] ?? false)->toBeTrue();
});
