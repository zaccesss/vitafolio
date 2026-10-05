# Changelog

All notable changes are recorded here. The format follows Keep a Changelog with date based entries.

## Unreleased

### Added

- A full-width header with an account menu showing your photo, name and handle, a four-column footer and a settings area with a page for each setting instead of two long forms.
- Pages for features, contact, a help centre with nine guides and a search box, what is new and the documentation, all linked from the footer and the sitemap.
- Complete terms of use and privacy policy, written for UK GDPR, with a contents list on each.
- A confirmation dialog for destructive actions in place of the browser's own prompt. Escape cancels and focus returns to the button.
- Breadcrumbs with structured data on help, legal and documentation pages. The home page describes the site and its search to search engines. The FAQ expands one question at a time and is marked up as an FAQ.
- A reading progress line on long pages, a back-to-top button and copy buttons on code blocks.
- A message button that stays in view on phones when reading someone's CV. A thank-you page after the contact form.
- Links tagged with `?utm_source=` show by their tag in Analytics.
- A thin progress bar at the top while the next page loads. Submit buttons disable themselves and say what they are doing, so a form cannot be sent twice.
- Analytics: profile views, QR code scans, PDF downloads and file opens are counted alongside CV views. An Analytics page shows them for the last 7, 30 or 90 days with a line chart, views per CV, referring sites, a busiest day and a table for every chart.

### Security

- A sign-in with Google, GitHub or LinkedIn that joins an existing account which never verified its email now removes every sign-in method, two-factor setup and session that account had first. Before, someone who registered an address they did not own kept a way in after the real owner arrived.
- An unverified account can only verify or sign out until it does: no passkeys, two-factor setup, connected accounts or settings changes.
- A suspended account is signed out on its next request and its remember-me cookie stops working, instead of only being refused at the sign-in form.
- Generated links, including password reset emails, always use the configured site address rather than a host header sent with the request. Only the forwarded address and scheme are trusted from the proxy.
- The forgot-password form answers the same way whether or not an address has an account. Sign-up, forgot-password and resend-verification are limited per address.
- Changing the email address needs the current password. The old address is told about the change.
- A profile photo can only be fetched with the version shown on a page the viewer was allowed to see. An old handle no longer redirects to a profile the viewer may not see. Unverified profiles are not shown to others.
- Share images and QR codes for CVs that are not public are no longer marked as publicly cacheable.
- Media shared between a CV and its copy is only deleted when nothing else uses it. The storage allowance is checked again after an upload finishes, so uploads running at the same time cannot add up past it.
- Account holders are emailed whenever their password is reset or changed, two-factor authentication is turned on or off, recovery codes are regenerated, a passkey is added or removed, a sign-in provider is connected or disconnected. A sign-in from a new browser is reported the same way. Every sign-in, failed attempt, lockout and moderation action is written to the audit log.
- A password reset signs every device out; a password change signs every other device out. Settings now lists every signed-in device with a sign-out button for each. Remember-me cookies last 30 days instead of five years.
- `/.well-known/change-password` sends password managers to the security settings. Signing out asks the browser to clear cached pages and site storage.
- The health check now fails when the database cannot answer. Content security policy breaks can be reported to the error tracker. Pages carry a cross-origin resource policy and a fuller permissions policy. The password reset page sends no referrer.
- The data download now includes connected accounts, passkeys, previous handles and signed-in devices. The privacy policy gained a separate right-to-object section and a statement on automated decisions.
- CI now runs `composer audit` and `npm audit`, scans the production image with Trivy and scans for committed secrets with Gitleaks. Every action is pinned to a commit.
- Crawlers that collect training data are kept off public CVs through `robots.txt`; search engines are not.
- Documentation gained secret rotation, incident response and maintenance mode sections.
- Profile photos are capped at 4,000 pixels a side so decoding one cannot exhaust memory. Search parameters sent as lists no longer cause an error.

### Fixed

- The production image starts on hosts that drop every Linux capability, such as Render. The server binary no longer asks for the port-binding capability it never uses.

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
