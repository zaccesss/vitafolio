# Demo recordings

These scripts record the clips on the site's Features page and in the main README, plus the README's
screenshots. They run against a throwaway local copy of the site filled with made-up people, so
re-recording after an interface change is one command and nothing touches the live site or its
database.

## What it needs

- PHP 8.4 with the project's Composer packages installed
- Node.js with the project's npm packages installed
- ffmpeg and the WebP tools: `brew install ffmpeg webp`

## Recording

```sh
scripts/demo/record.sh            # everything: eight clips, then the screenshots
scripts/demo/record.sh share      # one part only: a clip name or shots
scripts/demo/build-assets.sh      # turn the recordings into the site and README files
```

`record.sh` rebuilds a SQLite database at `storage/app/demo.sqlite`, loads the people in
[`seed.php`](seed.php), builds the front end and starts the site on port 8123 (`DEMO_PORT` changes it).
It then installs Playwright's Chromium and runs [`capture.mjs`](capture.mjs) twice: once in the light
theme with the screenshots, then again on a fresh database for the dark versions of the clips
(`DEMO_SCHEME=dark`). Recordings land in `scripts/demo/out`, which git ignores. When a single part
other than `build` is recorded, the seed creates Alex Morgan's finished CV so that part has something
to show. The seed also adds made-up employers and job listings on `example.com`, two tracked
applications, connected Google and GitHub accounts and an earlier support ticket with a reply. The
photo clip uses an illustrated avatar drawn by the capture script.

> [!NOTE]
> The browser maps `vitafolio.isaacadjei.me` to the local server, so every address on screen matches
> the real site even though the recording is local. Sign-up checks, error reporting, search
> engine pings and media uploads are switched off for the run. The sign-in providers get placeholder
> keys so their buttons show, but no clip completes a real Google, Microsoft or GitHub sign-in. The
> sign-up clip confirms its email address from the message the local mailer writes to
> `storage/logs/laravel.log`.

## What it produces

| Path | What it is |
| --- | --- |
| `public/demo/<clip>.webp` | The animated clip shown on the Features page and in the README, 960 pixels wide. Each file here also has a `-dark` copy for the dark theme |
| `public/demo/<clip>.mp4` | The full-quality video played on each clip's own page at `/features/demo/<clip>` |
| `public/demo/<clip>-still.webp` | The still frame shown when reduced motion is turned on |
| `docs/assets/screenshots/<page>-<theme>.webp` | Light and dark screenshots for the README |
| `docs/assets/demo/originals/` | The untouched recordings (`.webm`) and full-resolution screenshots (`.png`, 2560 by 1600) for reuse elsewhere, such as a portfolio |

The clips are `signup`, `build`, `share`, `compile`, `profile`, `check`, `jobs` and `support`. The pages
are `directory`, `dashboard`, `editor`, `look-and-privacy`, `public-cv`, `latex`, `latex-compiled`,
`jobs`, `applications`, `connected`, `check`, `support` and `signup`, each in `light` and `dark`.

## Changing a clip

Each clip in `capture.mjs` is a short list of steps with a caption before each one. Captions and the
moving cursor are drawn into the page, because browser recordings show neither. The still frame for
each clip is picked by time in `build-assets.sh`; move it if a clip changes length.
