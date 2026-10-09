# Changelog

All notable changes are recorded here. The format follows Keep a Changelog with date based entries.

## Unreleased

### Changed

- Demo clips and screenshots now cover the whole site apart from admin. New clips show creating an account, the photo with its crop tool, the handle and connected accounts, Check a CV, Jobs with My applications and a support ticket. Building, sharing and compiling a CV are recorded again from the current interface. Each Help guide shows the clip that matches it. The README adds the new clips and screenshots of sign-up, connected accounts, Check a CV, Jobs, My applications and a support ticket.
- More jobs: each Adzuna search reads up to three pages and new searches cover vacation schemes, year in industry, training contracts and graduate roles in engineering, accounting, nursing, teaching, marketing, science and software. An employer's own listing replaces a board's copy of the same role, since it links straight to the job with the full advert. Civil, highways and structural roles sit under Engineering rather than Software.
- Job fields are read from the title first; a board's own category only fills a gap, since boards file many roles under IT. Project management roles sit under Business. Employer names are matched without case or endings such as UK, Ltd and Group, so "Safran" and "SAFRAN UK" are one listing. Salary figures under £5,000, which are unlabelled day or hourly rates, are no longer shown.
- A tidier Jobs page: the search comes first, kinds of role are underlined tabs with how many jobs each holds, a result count shows and each card is compact with a two-line preview. A role a board posts once per city is one listing with its cities combined. Titles naming a year range such as 2027-28 count by the year they start. Electrical engineering roles sit under Engineering. The footer no longer links the source code. On Check a CV, a job board's advert is marked as a preview with a link to open the full advert.
- The Jobs search box looks for a job title or employer only, beside separate Field and Location filters. Role checks also catch hyphenated titles, year-long internships as placement years, insight experiences and summer schools, bank analyst and associate programmes and off-cycle roles.
- Jobs from Adzuna and Reed are classified by whole words in their titles instead of by the search that found them. Titles such as Internal Audit Manager, Senior Graduate Recruiter or Placement Coordinator are no longer listed as student roles, apprenticeships are left out and titles naming a year outside the cycle are skipped.
- Check a CV follows how the main tracking systems behave. A new Readable layout area (10 points) covers tables, columns, text boxes, images, contact details in the page header, scans, icon and decorative symbols that turn into garbled text, ligatures, letters spaced one by one, more than two pages, mixed date styles and UK personal details such as date of birth. It suggests a Profile when there is none and a Word file when a PDF has parsing problems. The page explains that the score is guidance and that most systems rank rather than reject.
- Crowdin's daily pull request merges itself once a check confirms no translation is removed or turned back into English. Translations committed here are uploaded on every push to main. The Crowdin branch is pushed by the automation account so its checks start without manual approval.
- The home page says Free for everyone, as plain text rather than a badge. The footer credit reads By isaacadjei.me.
- A CV's visibility shows as an icon beside its word instead of a rounded badge: a closed padlock for Private, an open padlock for Public and a link for Unlisted. It appears on the dashboard, the analytics table, the profile and the CV editor.

### Added

- The newest features are shown across the site. Help has guides for Checking a CV, Jobs and applications plus Support tickets. The FAQ answers where jobs come from, whether Check a CV keeps uploads, tracking roles found elsewhere, apprenticeships and getting help. The home page lists Check a CV and the jobs tracker. The footer links Jobs, Check a CV and a new support ticket. The Features page has four new cards. The terms gain a Jobs and Check a CV section: listings come from boards and employers, you apply under their own terms and the score is guidance only. The README lists Jobs, My applications and Support.
- My applications, a private tracker in the account menu. Save any role from the Jobs page with one click or add one found anywhere else. Each has a status (saved, applied, assessment, interview, offer, rejected or withdrawn) with tabs and counts, the date applied, a closing date and notes. A saved role keeps its title, employer and link after the listing closes. Applications are in the data download and are deleted with the account.
- View and apply counts clicks per listing per day without recording who clicked. Admins see the most-opened jobs of the last 30 days. Counts are deleted after a year.
- Support tickets. Anyone can open one from the Support page, signed in or not, with a category, a subject and a Markdown message of up to 5,000 characters plus up to three screenshots. Each ticket gets a reference such as VF-1042 and a confirmation email. Signed-in people see theirs under Your tickets; visitors use the private link they are emailed. Admins work through a support queue filtered by status and category. Every reply emails the other side. Screenshots are private files only the people on the ticket can open. A resolved ticket closes after 14 days without a reply. A closed ticket is deleted with its screenshots after a year. Deleting an account deletes its tickets. Tickets are in the data download and the privacy policy.
- Apprenticeships as their own kind of role on the Jobs page, from school-leaver levels to degree apprenticeships in every field.
- A Field filter on the Jobs page covering every area: software and IT, hardware and embedded, data and AI, engineering, finance, business, law, healthcare, science, creative and media, education, public sector and retail. Each listing shows its field. Adzuna's own category is used where it is specific; otherwise the field is read from the title.
- Employer listings on the Jobs page, received at `/jobs/feed` from employers' own hiring systems such as Greenhouse, Lever and Workday, so each one links straight to the employer's application page. Every listing is checked again on arrival: its kind of role is read from its title, roles outside the 2026 to 2027 cycle, closed roles and unsafe links are refused. An employer's advert is used only to fill in Check a CV. A listing stops showing three days after the feed last sent it.
- Spring weeks and insight days as their own kind of role on the Jobs page.
- A Jobs page with UK internships, placement years, graduate roles and part-time student jobs from the free Adzuna and Reed job APIs, fetched each night with the tidy-up. It filters by kind of role, keyword and location. Each listing links to the board it came from to apply, with the attribution Adzuna's terms require. Signed-in people can check a CV against any listing, which opens Check a CV with the advert filled in. Listings are removed once they close or after 30 days.
- A Check a CV page, linked from the menu and the dashboard. It scores a Vitafolio CV or an uploaded PDF or Word file out of 100 across heading clarity, contact details, skills, education, experience and keywords, the way an applicant tracking system reads it. The report lists what was found, what is missing, layout problems such as tables, columns, images, text boxes and contact details in the page header, plus fixes. A pasted job advert shows which of its keywords the CV has. Reports download as Markdown and the parsed CV as JSON. Uploads are read in memory and never stored. `php artisan cv:analyse` does the same from the command line.
- The cover letter tab has templates for an internship, a placement year, a graduate role and a part-time or student job. Choosing one fills the box with a structure to complete, signed with the owner's name; nothing is saved until they press Save. An existing letter gets a warning and a way back.
- Each demo clip has its own page at `/features/demo/<clip>` with a video player, a Back to Features link, a written description of what happens and links to the other clips. Selecting a clip on the Features page opens it there instead of the bare video file.
- Dark versions of the demo clips. The Features page, the clip pages and the README show the one that matches the theme in use.
- A See it in action section on the Features page with the three demo clips, for people new to the site. Each clip shows a still frame when reduced motion is turned on and opens a full-quality video. Signed-out visitors get a See how it works button on the home page that leads there. The new text is in all seven languages.
- A demo recording kit in `scripts/demo`. One command records the clips and screenshots from a throwaway local copy of the site filled with made-up people. A second turns them into the site and README files. The untouched recordings and full-resolution screenshots are kept in `docs/assets/demo/originals`.
- A See it in action section in the README: three short captioned clips (building a CV, styling and sharing it and compiling a PDF from LaTeX), each linking to a full-quality video, plus six screenshots that follow GitHub's light or dark theme. The clips show a still frame when reduced motion is turned on.
- Community translation through the Vitafolio project on Crowdin. New English strings are uploaded when they change and finished translations come back as a daily pull request.
- Endorsements from people who know a CV owner's work. A signed-in, verified account writes one from the CV's page, with how they know the owner and an optional role or context. Each one waits until the owner approves it on the editor's new Endorsements tab, where it can also be hidden or deleted. The owner is emailed and sees a waiting count on the dashboard. Only approved endorsements show and only where the CV itself is visible. Writers can edit or withdraw their own. Each person can endorse a CV once. The form has Turnstile and a rate limit. Endorsements can be reported to moderators, who can hide them. Both people's data downloads include them. Deleting either account removes them. The privacy policy describes what is stored.
- The site in seven languages: English, Spanish, French, Brazilian Portuguese, Simplified Chinese, Arabic and Urdu. A first visit follows the browser's language. A language menu in the header and the footer names each language in its own script and remembers the choice, on the account when signed in, so emails follow it too. Arabic and Urdu pages read right to left, with Noto Naskh Arabic and Noto Nastaliq Urdu served from the site. Legal pages and the documentation stay in English, with a note saying so.
- A document language for each CV under Look and privacy. It sets the language of the CV's labels on its page and in its PDF and Word files, whatever language a visitor uses. Generated PDFs declare that language, lay out right to left for Arabic and Urdu and embed Noto fonts for Chinese, Arabic and Urdu text. They stay PDF/UA-1. What people write is never translated.
- `crowdin.yml` for community translation through Crowdin's GitHub integration. `php artisan vitafolio:translations` keeps the English source file in step with the code.

- A cover letter alongside each CV. Write it on the editor's new Cover letter tab, with an optional line saying who it is for. It opens from the CV's own address at `/letter` in the CV's theme. It also downloads as a PDF or a Word document. It follows exactly the same visibility as its CV. A CV without a letter shows no trace of one.
- Download a CV as a Word document, beside the PDF on the CV page and in the editor's Export section. It uses Word's own title, heading and list styles, so it stays editable and reads well in screen readers and applicant tracking systems. It follows the same visibility rules as the PDF.
- Browse CVs finds people as well as CVs. A search by name or @handle shows matching public profiles in a People row above the CV results, including people who have not published a CV yet. Unlisted and private profiles never appear.
- Three more LaTeX starter templates: Sidebar, a two-column layout with contact and skills beside the main story. Elegant, a centred serif page with small capitals. Minimal, a clean sans-serif page with plenty of white space. All seven templates compile with pdfLaTeX, XeLaTeX and LuaLaTeX on the core download.
- A link to Vitafolio's LinkedIn page in the footer, also listed in the site's structured data for search engines.
- The LinkedIn page's logo, cover image and profile text in `resources/brand/linkedin`, linked from the documentation.
- Each sign-in button and connected account shows its provider's official logo beside the text: Google's four-colour G, the GitHub mark, Microsoft's four squares and LinkedIn's in.
- Sign in with GitHub and Microsoft. The Microsoft sign-in app names vitafolio.isaacadjei.me as its publisher through a verification file at `/.well-known/microsoft-identity-association.json`.
- A full-width header with an account menu showing your photo, name and handle, a four-column footer and a settings area with a page for each setting instead of two long forms.
- Pages for features, contact, a help centre with nine guides and a search box, what is new and the documentation, all linked from the footer and the sitemap.
- Complete terms of use and privacy policy, written for UK GDPR, with a contents list on each.
- The Import tab points to the File and LaTeX tab for PDF and Word CVs.
- Form hints now sit under their box on every form, so fields side by side always line up. Required fields carry a red asterisk, announced as "required" to screen readers. Optional fields still say so.
- A copyright and licences page, linked from the copyright line and the Legal column of the footer. It covers who owns CVs, the MIT licence for the code and the name and logo. It also lists every third-party font and library.
- The sitemap lists the nine help guides and the copyright page. Every entry carries a last-modified date.
- The CV and LaTeX editors have a Help link. Links that would leave an editor, such as Preview, open in a new tab and say so, so unsaved work is never lost.
- In production the host's own address redirects to the site address with the path kept. Pages there had loaded without styles, because styles are served from the site address.
- The theme button shows a sun, a moon or a screen for light, dark and system, so the current choice is clear at a glance.
- The LaTeX editor labels its shortcuts with Cmd on Apple devices and Ctrl elsewhere. They work even when focus is outside the editor, so the browser never saves the page instead.
- The accessibility statement lists every keyboard shortcut for Windows, Linux and Mac, including the Safari setting that stops Tab skipping links. It also states the supported browsers.
- Escape closes the mobile menu. Windows contrast themes keep the current page, progress and busy states visible. Pages fill phone screens correctly when the address bar hides. Safari no longer shows an extra arrow on FAQ questions.
- A confirmation dialog for destructive actions in place of the browser's own prompt. Escape cancels and focus returns to the button.
- Breadcrumbs with structured data on help, legal and documentation pages. The home page describes the site and its search to search engines. The FAQ expands one question at a time and is marked up as an FAQ.
- A reading progress line on long pages, a back-to-top button and copy buttons on code blocks.
- A message button that stays in view on phones when reading someone's CV. A thank-you page after the contact form.
- Links tagged with `?utm_source=` show by their tag in Analytics.
- A thin progress bar at the top while the next page loads. Submit buttons disable themselves and say what they are doing, so a form cannot be sent twice.
- Analytics: profile views, QR code scans, PDF downloads and file opens are counted alongside CV views. An Analytics page shows them for the last 7, 30 or 90 days with a line chart, views per CV, referring sites, a busiest day and a table for every chart.

### Changed

- Crowdin translation pull requests are opened by the project's automation account instead of the built-in Actions token, so they run CI on their own.
- Pint no longer checks the `lang/` folder. Its files are now exported by Crowdin in Crowdin's own formatting, so translation pull requests no longer fail the style check over quote style.
- Generated CV and cover letter PDFs are tagged PDF/UA-1 files, so screen readers read them as well as the web page. Each one declares British English as its language and carries its own title. Headings become bookmarks. Paragraphs, bulleted lists, the links table and every link carry structure tags in reading order. The photo has alt text. The PDFs are now made by Typst, which refuses to write a file that breaks the standard. mPDF stays only as a fallback for a machine without Typst.
- The privacy policy, help guides, features page and documentation list Google, GitHub and Microsoft as the sign-in options. LinkedIn sign-in is not offered.
- The unused LinkedIn sign-in setup is gone: its provider entry, its keys in `.env.example`, its logo and its mentions in the code and documentation. The LinkedIn page link in the footer and structured data stays.
- New CVs start private and new profiles start unlisted, so nothing appears in Browse CVs until its owner publishes it. A Publish button on the dashboard makes a CV public in one step. Existing CVs and profiles keep their settings.
- The project's contact address is now vitafolio@isaacadjei.me.

### Security

- The security check on public forms is enforced in every case. The contact forms and CV reports now always require it when it is switched on.
- `shell-quote` 1.12 under `concurrently` for a critical command injection advisory in `quote()`. `concurrently` pins 1.9.0, so a scoped override lifts it. It only runs the local development scripts.
- `katex` 0.18 under Mermaid for a prototype pollution advisory and `postcss-selector-parser` 7 under the Typography plugin for a CPU exhaustion advisory. Neither parent has a release that allows the fixed version yet, so scoped overrides lift them. The built CSS is byte for byte the same as before.
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

- The nightly job fetch no longer fails when a role posted in many cities builds a location longer than the database allows. Long values are now cut to fit, dots included.
- The contents links on the Documentation page jump to their sections. Its headings had no ids, so a link changed the address but the page stayed where it was. Changelog headings can now be linked to as well.
- The photo crop tool shows the chosen photo again. The tool moved to Settings, Photo, but the content security policy still allowed a local preview only on the old profile page, so the framing circle never appeared and every photo was cropped to the centre.
- The nightly job fetch no longer emails an error for every Adzuna search that is briefly refused. A failed search is tried twice more after a short pause, four searches run at a time instead of eight and one alert is raised only when more than a quarter of searches fail, naming which ones and why.
- The nightly job fetch no longer stops at the one-minute limit for a web request. Adzuna searches run eight at a time and listings are saved in batches of 200 after two up-front reads instead of three queries each. The nightly call may run for up to five minutes. Every workflow job also has a time limit.
- The job advert box on Check a CV said optional twice.
- Check a CV scored the selected Vitafolio CV instead of an uploaded file when the Vitafolio option was still chosen, so different uploads got the same score. Choosing a file now selects it, a sent file is always the one checked and the report names the CV it checked.
- Check a CV finds a name written with a nickname in brackets or placed after the contact line, recognises more standard headings such as Research & Publications and Spoken Languages, ignores filler words in job adverts and matches singular and plural forms. Strong verbs in any tense now count.
- Translations committed to the repository are no longer replaced with English by the next Crowdin sync. A manual run of the sync workflow can now upload them to Crowdin first and approve them, so they come back down.
- The developer documentation lists all seven LaTeX starter templates instead of four.
- Arabic and Urdu text in generated PDF, Word and LaTeX files no longer breaks apart. Lines were split on a byte that is part of some Arabic letters.
- The site went down on 6 October when the free database plan powered the database off for inactivity. The database now runs on TiDB Cloud Starter in Frankfurt, which stays on, with every table and row copied across and checked. The privacy policy names the new host.
- Saving your public profile and deleting your account work on the live site. The redirects from the old /profile and /account addresses answered every kind of request. Once routes were cached in production they caught those forms first, so nothing was saved. The redirects now answer page visits only.
- An uploaded or LaTeX-compiled PDF opens in Chrome instead of showing a blocked page. The file carried the site-wide security policy as well as its own. That policy forbids the browser's PDF viewer. A PDF still cannot load or run anything; Word files keep their sandbox and download.
- The footer's Legal column listed Copyright twice.
- LaTeX CVs compile in the editor. A bullet list made pdfLaTeX stop with no output, because its default bullet needs a font outside the core packages. The starter templates now load Latin Modern, which is in the core set and covers bullets and accented letters. All four templates compile with pdfLaTeX, XeLaTeX and LuaLaTeX.
- Flowcharts on the documentation and What is new pages are drawn as diagrams, with the source available as text underneath, instead of showing as code.
- The README explains the name and its flowchart shows every service now in use: Turnstile, Aiven, the LaTeX engine host and the uptime monitor.
- The LaTeX editor starts its engine. Browsers refuse to run a background worker from another site, so the engine's worker now starts from a local copy that loads the rest of the engine from its host. The first download drops from about 680 MB to about 120 MB: the starter templates use only core packages. Larger package collections download only when a document needs them. Shortcut labels show Cmd on Macs running Chrome.
- Compiling LaTeX works on the live site. The engine is published from its own repository, [vitafolio-latex](https://github.com/zaccesss/vitafolio-latex). The editor now loads the TeX Live packages the starter templates need. It shows download progress and says how large the first download is.
- The issue form's account help link uses vitafolio@isaacadjei.me.
- Deleting your account or signing out one device now works when the password has not been confirmed recently. Confirming used to send you back to the settings page without finishing the action.
- The password box on Name and email is marked optional, since it is only needed when changing the address.
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
