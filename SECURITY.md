# Security Policy

Vitafolio stores people's CVs, contact details and sign-in methods, so security reports are taken
seriously and handled first.

## Supported versions

Only the latest release on `main` and the live site receive fixes.

## Scope

- Seeing, changing or deleting a CV, profile, file or project that belongs to someone else
- Reaching a private or hidden CV, its file or its photo without being its owner or an admin
- Bypassing sign-in, email verification, two-factor authentication, passkeys or a suspension
- A sign-in provider joining or taking over an account it should not
- Cross-site scripting, content security policy bypasses or HTML injection through any field, link,
  upload or LaTeX output
- An upload that is stored or served as something other than its checked type
- Getting past the storage allowance, upload limits or rate limits in a way that could exhaust the
  service
- Learning a CV owner's email address through the message relay
- Leaking secrets, session data or other users' details through errors, logs, exports or headers

## Out of scope

- Vulnerabilities in Laravel, the PHP extensions, FrankenPHP or other dependencies, which belong with
  those projects unless Vitafolio uses them unsafely
- Missing best-practice headers with no demonstrated impact
- Denial of service through sheer request volume
- Social engineering, physical access or attacks needing a compromised device
- Content a CV owner chose to make public

## Reporting a vulnerability

> [!IMPORTANT]
> Report privately, never in a public issue. Use
> [GitHub private vulnerability reporting](https://github.com/zaccesss/vitafolio/security/advisories/new)
> or email contact@isaacadjei.me with the steps to reproduce and the impact. Expect an
> acknowledgement within a few days. Please give reasonable time for a fix before sharing details.

Test only with accounts you own. Never access or keep data that belongs to someone else.

## The shared policy

> [!NOTE]
> The full policy is in [zaccesss/security-policy](https://github.com/zaccesss/security-policy) and on
> [isaacadjei.me](https://isaacadjei.me/security-policy). This file takes precedence where the two
> differ.
