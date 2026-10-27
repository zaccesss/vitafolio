# Workflows

| Workflow | Runs on | What it does |
| --- | --- | --- |
| [CI](ci.yml) | Pull requests and pushes to `main` | Code style with Pint, static analysis with Larastan, the test suite, `composer audit`, `npm audit`, the front-end build and a build of the production image scanned with Trivy |
| [Gitleaks Scan](gitleaks-scan.yml) | Pull requests and pushes to `main` | Scans the working tree for committed secrets |
| [Markdown Lint](markdownlint.yml) | Pull requests and pushes to `main` | Lints every Markdown file against `.markdownlint.json` |
| [Nightly tidy-up](nightly-tidy.yml) | By hand; the nightly run at 03:30 London comes from an external scheduler | Calls the site's `/cron` address, which clears expired sessions, cache rows, reset tokens, released handles and old view stats and brings the site out of maintenance mode once its database answers again |

> [!NOTE]
> The nightly tidy-up needs a `CRON_TOKEN` repository secret matching the site's own `CRON_TOKEN` and a
> `SITE_URL` repository variable. Without them it skips without failing.
