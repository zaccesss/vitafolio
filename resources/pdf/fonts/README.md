# PDF fonts

Fonts Typst embeds in generated CV and cover letter PDFs for text outside the Latin, Greek and
Cyrillic scripts. DejaVu, used for those, ships with mPDF.

| Font | Files | Source |
| --- | --- | --- |
| Noto Naskh Arabic 2.021 | `NotoNaskhArabic-Regular.ttf`, `NotoNaskhArabic-Bold.ttf` (unhinted) | [notofonts/arabic](https://github.com/notofonts/arabic/releases/tag/NotoNaskhArabic-v2.021) |
| Noto Nastaliq Urdu 4.000 | `NotoNastaliqUrdu-Regular.ttf`, `NotoNastaliqUrdu-Bold.ttf` (unhinted) | [notofonts/nastaliq](https://github.com/notofonts/nastaliq/releases/tag/NotoNastaliqUrdu-v4.000) |
| Noto Sans SC 2.004 | `NotoSansSC-Regular.otf`, `NotoSansSC-Bold.otf`, fetched by `scripts/fetch-pdf-fonts.sh` and not kept in git | [notofonts/noto-cjk](https://github.com/notofonts/noto-cjk/releases/tag/Sans2.004) |

Each font is copyright The Noto Project Authors and is licensed under the
[SIL Open Font License, Version 1.1](https://openfontlicense.org). The licence notice and its address
are also carried in each font file's own metadata.
