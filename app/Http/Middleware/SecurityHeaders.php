<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // the latex editor's code editor injects its own style tags, so that page gets a
        // one-time nonce for them instead of allowing inline styles everywhere
        $nonce = $request->routeIs('cvs.latex') || str_contains($request->path(), '/latex') ? Vite::useCspNonce() : null;

        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        // the vite dev server injects its own scripts, so the strict policy only applies to built assets
        if (! Vite::isRunningHot()) {
            // only the latex editor runs webassembly, so only that page allows it, plus the
            // worker and engine files it needs and a blob frame for the pdf preview
            $latex = $request->routeIs('cvs.latex');
            $latexAssets = $latex ? ' '.(parse_url((string) config('vitafolio.latex_assets_url'), PHP_URL_SCHEME) ? preg_replace('#^(https?://[^/]+).*#', '$1', (string) config('vitafolio.latex_assets_url')) : '') : '';

            $headers['Content-Security-Policy'] = implode('; ', [
                "default-src 'self'",
                "script-src 'self' https://challenges.cloudflare.com https://static.cloudflareinsights.com".($latex ? " 'wasm-unsafe-eval'" : ''),
                'worker-src '.($latex ? "'self' blob:" : "'self'"),
                "style-src 'self'".($nonce ? " 'nonce-{$nonce}'" : ''),
                // the photo cropper previews the chosen file before it is uploaded
                "img-src 'self' data: https://res.cloudinary.com".($request->routeIs('profile.edit') ? ' blob:' : ''),
                "media-src 'self' https://res.cloudinary.com",
                "font-src 'self'",
                "connect-src 'self' https://cloudflareinsights.com".rtrim($latexAssets),
                'frame-src https://challenges.cloudflare.com'.($latex ? ' blob:' : ''),
                "frame-ancestors 'none'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            ]);
        }

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }
}
