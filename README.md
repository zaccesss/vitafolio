# Vitafolio

Build, store and share every version of a CV, each with its own theme, privacy setting and link.

![Vitafolio: every version of your CV, in one place](resources/brand/social-preview.png)

Vitafolio gives each person a profile and as many named CVs as they need: one for software roles, one
for research, one for a part-time job. Each CV can be public, unlisted or private. A CV can be built
in the editor or uploaded as a PDF or Word file. It can also be written in LaTeX and compiled in the
browser.

## Features

| Area | What it does |
| --- | --- |
| Profiles | A handle at `/@name`, a photo with a crop tool, a headline, links with recognised sites and a visibility setting |
| CVs | Up to ten per account, each with its own address, theme, accent colour, font, section order and visibility |
| Content | Rich sections, skills as tags, projects with images or short videos, JSON Resume import and export |
| Files | Uploaded PDF and Word files. LaTeX compiled in the browser with pdfLaTeX, XeLaTeX or LuaLaTeX |
| Sharing | A clean link, a QR code, a generated PDF and a share image for each CV, plus private view counts |
| Directory | Public CVs listed with search, skill tags, availability and university filters |
| Sign-in | Email and password, passkeys, two-factor authentication, plus Google, GitHub, Microsoft and LinkedIn |
| Safety | Ownership checks on every change, reports with a moderation queue, suspensions and a strict content security policy |
| Accessibility | WCAG 2.2 AA colours in light and dark themes, full keyboard use, reduced motion and a plain CV layout |

## Stack

### Built with

<table>
  <tr>
    <td align="center" width="96"><img src="docs/assets/stack/laravel.svg" width="56" height="56" alt=""><br><sub><b>Laravel</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/php.svg" width="56" height="56" alt=""><br><sub><b>PHP 8.4</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/vuedotjs.svg" width="56" height="56" alt=""><br><sub><b>Vue</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/alpinedotjs.svg" width="56" height="56" alt=""><br><sub><b>Alpine.js</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/tailwindcss.svg" width="56" height="56" alt=""><br><sub><b>Tailwind CSS</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/vite.svg" width="56" height="56" alt=""><br><sub><b>Vite</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/mysql.svg" width="56" height="56" alt=""><br><sub><b>MySQL</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/latex.svg" width="56" height="56" alt=""><br><sub><b>LaTeX</b></sub></td>
  </tr>
</table>

### Runs on

<table>
  <tr>
    <td align="center" width="96"><img src="docs/assets/stack/docker.svg" width="56" height="56" alt=""><br><sub><b>Docker</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/render.svg" width="56" height="56" alt=""><br><sub><b>Render</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/cloudflare.svg" width="56" height="56" alt=""><br><sub><b>Cloudflare</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/cloudinary.svg" width="56" height="56" alt=""><br><sub><b>Cloudinary</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/resend.svg" width="56" height="56" alt=""><br><sub><b>Resend</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/sentry.svg" width="56" height="56" alt=""><br><sub><b>Sentry</b></sub></td>
    <td align="center" width="96"><img src="docs/assets/stack/githubactions.svg" width="56" height="56" alt=""><br><sub><b>GitHub Actions</b></sub></td>
  </tr>
</table>

Laravel Fortify handles accounts, two-factor authentication and passkeys. Laravel Socialite handles
the sign-in providers. PDFs come from mPDF, images from GD and the server is FrankenPHP on Alpine
Linux. Each choice is explained in the [design decisions](docs/DOCUMENTATION.md#design-decisions).

## Architecture

```mermaid
flowchart LR
    subgraph PEOPLE["People"]
        OWNER["CV owners<br/>build and share"]
        VISITOR["Visitors and recruiters<br/>read, download, message"]
    end
    subgraph EDGE["Cloudflare"]
        CF["DNS, spam protection<br/>and cookieless analytics"]
    end
    subgraph HOST["Render"]
        APP["FrankenPHP<br/>Laravel 13 on PHP 8.4"]
    end
    PEOPLE --> CF --> APP
    APP --> DB[("MySQL<br/>accounts, CVs, photos")]
    APP -- "signed requests" --> MEDIA[("Cloudinary<br/>CV files and project media")]
    APP -- "verification and alerts" --> MAIL["Resend"]
    APP -. "errors" .-> SENTRY["Sentry"]
    OWNER -- "first compile only" --> TEX["LaTeX engine files<br/>compiled in the browser"]
    CRON["Nightly scheduler"] -- "POST /cron" --> APP
    APP -- "public page changed" --> SEARCH["Search engines<br/>sitemap and IndexNow"]
```

## Running it locally

You need PHP 8.4 with the `gd`, `intl`, `pdo_mysql`, `zip` and `bcmath` extensions, Composer,
Node.js 26 and MySQL 8.

```sh
git clone https://github.com/zaccesss/vitafolio.git
cd vitafolio
composer install
npm ci
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Then open `http://localhost:8000`, create an account and confirm it from the email written to
`storage/logs/laravel.log` (mail is logged rather than sent until a mailer is configured). Run
`php artisan vitafolio:make-admin you@example.com` to reach the moderation page.

> [!TIP]
> Every outside service is optional. Without its keys, a feature switches itself off: no sign-in
> buttons for unset providers, no media uploads without Cloudinary and no LaTeX compiling without
> the engine files. The rest of the site works as normal.

## Checks

```sh
php artisan test            # feature and unit tests on an in-memory database
vendor/bin/pint --test      # code style
vendor/bin/phpstan analyse  # static analysis
npm run build               # front-end build
```

## Documentation

- [Documentation](docs/DOCUMENTATION.md): architecture, data model, security, configuration, deployment and operations
- [Changelog](CHANGELOG.md), [roadmap](ROADMAP.md) and [accessibility](ACCESSIBILITY.md)
- [Contributing](CONTRIBUTING.md), [security policy](SECURITY.md) and [support](SUPPORT.md)

## Licence

Released under the [MIT Licence](LICENSE). The in-browser LaTeX engine is a separate component under
its own licence; see [NOTICE.md](NOTICE.md).
