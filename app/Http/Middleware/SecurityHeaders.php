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
            // the reset page carries a token in its address, so it sends no referrer at all
            'Referrer-Policy' => $request->routeIs('password.reset') ? 'no-referrer' : 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), bluetooth=(), serial=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];
        // pages are for this site alone; photos, share images and qr codes are meant to be embedded elsewhere
        if (! $request->routeIs('avatar', 'cv.og', 'cv.qr')) {
            $headers['Cross-Origin-Resource-Policy'] = 'same-origin';
        }

        // the vite dev server injects its own scripts, so the strict policy only applies to built assets
        if (! Vite::isRunningHot()) {
            // only the latex editor runs webassembly, so only that page allows it, plus the
            // worker and engine files it needs and a blob frame for the pdf preview
            $latex = $request->routeIs('cvs.latex');
            $latexAssets = $latex ? ' '.(parse_url((string) config('vitafolio.latex_assets_url'), PHP_URL_SCHEME) ? preg_replace('#^(https?://[^/]+).*#', '$1', (string) config('vitafolio.latex_assets_url')) : '') : '';

            $headers['Content-Security-Policy'] = implode('; ', [
                "default-src 'self'",
                // the latex worker starts from a local blob and imports the engine scripts from their host
                "script-src 'self' https://challenges.cloudflare.com https://static.cloudflareinsights.com".($latex ? " 'wasm-unsafe-eval'".rtrim($latexAssets) : ''),
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
                // breaks in the policy are reported to the error tracker when a reporting address is set
                ...(config('vitafolio.csp_report_uri') ? ['report-uri '.config('vitafolio.csp_report_uri'), 'report-to csp'] : []),
            ]);
            if (config('vitafolio.csp_report_uri')) {
                $headers['Reporting-Endpoints'] = 'csp="'.config('vitafolio.csp_report_uri').'"';
            }
        }

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // a response that sets its own policy keeps it alone. A second policy would be enforced as
        // well. The site-wide one forbids the browser's pdf viewer on an uploaded cv
        if ($response->headers->has('Content-Security-Policy')) {
            unset($headers['Content-Security-Policy']);
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value, false);
        }

        return $response;
    }
}
