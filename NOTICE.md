# Notice

Vitafolio's own code is released under the [MIT Licence](LICENSE), Copyright (c) 2026 Isaac Adjei
<https://isaacadjei.me>.

The built site also includes third-party components under their own licences. They are used
unmodified. Their licences apply to them rather than the MIT Licence.

## The in-browser LaTeX engine

| Component | Licence | Source |
| --- | --- | --- |
| [texlyre-busytex](https://github.com/TeXlyre/texlyre-busytex) | AGPL-3.0-or-later | [github.com/TeXlyre/texlyre-busytex](https://github.com/TeXlyre/texlyre-busytex) |
| TeX Live engines and packages loaded by it | The free licences of each TeX Live component | [tug.org/texlive](https://tug.org/texlive/) |

> [!NOTE]
> The LaTeX engine is loaded only on the LaTeX editor page. Its complete source is available at the
> link above. The complete source of Vitafolio itself is in this repository. Anyone running a modified
> copy of the engine must offer their users its source, as the AGPL requires.

## Other bundled components

| Component | Licence |
| --- | --- |
| Inter, Source Serif 4 and JetBrains Mono fonts | SIL Open Font License 1.1 |
| GSAP | GreenSock Standard License, free for this use |
| Vue, Alpine.js, CodeMirror, SortableJS and the Laravel passkeys client | MIT |
| Technology logos in `docs/assets/stack` | Shapes from Simple Icons (CC0 1.0). Each logo remains a trademark of its owner |

PHP dependencies and their licences are listed in `composer.lock`; JavaScript dependencies are listed
in `package-lock.json`.
