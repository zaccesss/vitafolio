# Contributing

Bug fixes, accessibility improvements, clearer wording and well-scoped features are welcome.

## What belongs here

- A bug, with steps to reproduce it
- An accessibility problem, which is treated as a bug
- A new CV theme, LaTeX template or recognised link site
- Translations of the interface wording are not supported yet; open an issue first if you want to help
  with that

For a larger feature, open an issue first so the approach can be agreed before you build it.

## Setting up

Follow [Running it locally](README.md#running-it-locally) in the README. Every outside service is
optional, so a database is all you need to start.

## Making a change

1. Fork the repository and create a branch named `fix/<short-description>` or
   `feat/<short-description>`.
2. Make your change. Keep comments short and explain why the code is the way it is, not what it does.
3. Add or update tests for anything that changes behaviour, especially ownership and visibility rules.
4. Run every check:

   ```sh
   php artisan test
   vendor/bin/pint
   vendor/bin/phpstan analyse
   npm run build
   ```

5. Open a pull request with a clear title and a short description of what changed and why.

> [!IMPORTANT]
> Never commit real personal details, keys or secrets. Use example values such as `example.com` in
> tests and documentation. Settings belong in `.env`, which is never committed.

## Guidelines

- Pages must work with a keyboard alone and meet WCAG 2.2 AA contrast in both themes.
- No inline scripts or styles: the content security policy forbids them. Register Alpine components in
  `resources/js/app.js`.
- Anything that changes a CV goes through `CvPolicy`.
- New settings go in `config/vitafolio.php` with an environment variable and a line in `.env.example`.

## Reporting problems

Open an [issue](https://github.com/zaccesss/vitafolio/issues/new/choose) with what you were doing,
what you expected and what happened instead. Report security problems privately through
[SECURITY.md](SECURITY.md).

## The shared guide

> [!NOTE]
> The shared contributing guide is at [zaccesss/contribute](https://github.com/zaccesss/contribute)
> and on [isaacadjei.me](https://isaacadjei.me/contribute). This file takes precedence where the two
> differ.
