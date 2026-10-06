# LinkedIn page

Vitafolio's company page is [Vitafolio on LinkedIn](https://www.linkedin.com/company/vitafolio26/). It was created on 6 October 2026. This folder holds the images and the profile text the page uses, so it can be checked or rebuilt from the repository.

> [!NOTE]
> The page address ends in `vitafolio26` because `linkedin.com/company/vitafolio` belongs to an unrelated company. Always link the `vitafolio26` address.

The site links the page from the footer and lists it in the structured data through the `SITE_LINKEDIN_URL` environment variable. The page is not a sign-in provider: LinkedIn sign-in is not offered.

## Page details

| Field | Value |
| --- | --- |
| Name | Vitafolio |
| Page URL | [linkedin.com/company/vitafolio26](https://www.linkedin.com/company/vitafolio26/) |
| Website | [vitafolio.isaacadjei.me](https://vitafolio.isaacadjei.me) |
| Industry | Software Development |
| Organisation size | 0 to 1 employees |
| Organisation type | Self-owned |

## Tagline

Every version of your CV, in one place. Build, store and share each one with its own theme, privacy and link.

## About

Vitafolio is a free place to build, store and share every version of your CV. Keep one for software roles, one for research and one for part-time work, each with its own theme, privacy setting and link.

Build a CV in the editor or upload a PDF or Word file. You can also write it in LaTeX and compile it right in your browser. Share it with a clean link, a QR code for printed copies or a polished PDF. You can also see how often it is opened.

Every CV can be public, unlisted or private. Nothing goes public until you choose. Sign in with email, a passkey, Google, GitHub or Microsoft, with two-factor authentication available.

Vitafolio is open source and built with accessibility in mind: light and dark themes, full keyboard use and a plain layout for applicant tracking systems.

## Images

| File | Size | Use |
| --- | --- | --- |
| [`vitafolio-logo.png`](vitafolio-logo.png) | 400 by 400 | Page logo |
| [`vitafolio-cover.png`](vitafolio-cover.png) | 2256 by 382 | Cover image |

Both are made from the brand mark in [`resources/brand`](..), the same source as the site's icons, not from a screenshot. The logo is the mark on its navy tile, centred with a margin so LinkedIn's crop does not clip it. The cover sets the mark, the name and the line "Every version of your CV, in one place." on a navy gradient with a gold rule along the bottom edge. The mark and name sit right of centre, clear of the left side where the logo overlaps the cover on desktop.

These files are not built by `scripts/brand-assets.sh`. Replace them by hand if the brand mark changes, then upload the new versions to the page.
