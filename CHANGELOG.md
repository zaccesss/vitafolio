# Changelog

All notable changes are recorded here. The format follows Keep a Changelog with date based entries.

## Unreleased

### Added

- Discussion forms for General, Ideas, Q&A and Show and tell, with questions and ideas pointed to
  Discussions from the support and contributing guides and the issue chooser.

- Accounts with email verification, breach-checked passwords, two-factor authentication, passkeys and
  sign-in through Google, GitHub, Microsoft or LinkedIn. A provider only joins an existing account when
  it confirms the email is verified.
- Profiles at `/@handle` with a photo cropper, headline, bio, recognised links and a visibility setting.
  Handles change once every 30 days and old ones redirect for 30 days.
- Up to ten named CVs per account, each public, unlisted or private, with four themes, nine accent
  colours, three fonts and a drag or keyboard section order.
- Projects with images or short videos, uploaded PDF and Word files and an in-browser LaTeX editor with
  four starter templates.
- Sharing through a clean link, a QR code, a generated PDF, a share image and private view counts.
- A directory of public CVs with search, skill tags, availability and university filters.
- Visitor messages that never reveal the owner's address, reports with a moderation queue, admin email
  alerts and suspensions.
- A storage allowance per account, upload limits, file type checks by content and Cloudinary storage
  for CV files and project media.
- A strict content security policy, security headers, rate limits, a sitemap, IndexNow, structured
  data, `security.txt`, a web app manifest and an offline page.
- A nightly tidy-up endpoint, error tracking through Sentry and a FrankenPHP production image that runs
  as an unprivileged user.
- Feature and unit tests, code style and static analysis checks, CI workflows, Dependabot, issue forms,
  a pull request template, community files and full documentation.
