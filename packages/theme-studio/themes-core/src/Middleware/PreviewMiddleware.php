<?php

declare(strict_types=1);

namespace Capell\Themes\Core\Middleware;

use Capell\Themes\Core\Preview\PreviewMode;
use Closure;
use Illuminate\Http\Request;

class PreviewMiddleware
{
    public function __construct(private readonly PreviewMode $preview) {}

    public function handle(Request $request, Closure $next): mixed
    {
        $token = $request->query($this->preview->tokenParam());
        $isPreview = $token !== null && $this->preview->validateToken((string) $token, $request->path());

        if ($isPreview) {
            view()->share('isPreviewMode', true);
        }

        return $next($request);
    }
}
