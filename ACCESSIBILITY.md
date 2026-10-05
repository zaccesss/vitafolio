# Accessibility

A CV site is only useful if everyone can build one and everyone can read one. Vitafolio aims for WCAG
2.2 AA throughout. The live site carries its own statement at `/accessibility`.

## What the site does

| Need | What Vitafolio does |
| --- | --- |
| Screen readers | Real headings and landmarks, labels on every field, form errors listed at the top and linked to each field, live announcements for saves and copies |
| Keyboard use | Every control can be reached with Tab and shows a clear focus outline. The section order and photo framing both work with buttons or arrow keys as well as dragging |
| Low vision | Text and controls meet AA contrast in light and dark themes. Layouts reflow at 400% zoom |
| Colour vision differences | Colour is never the only signal: badges, states and links all carry text too. Every CV accent colour is checked for contrast |
| Motion sensitivity | Animation is decoration only and stops when the device asks for reduced motion. Videos never play on their own |
| Reading CVs | The Plain CV theme is built for screen readers and applicant tracking systems. Every project image or video has a description |

## Known gaps

> [!WARNING]
> PDFs compiled from LaTeX may not carry the tags screen readers need. The CV's web page always shows
> the same content in an accessible form, so share that link where possible.

- Uploaded PDF and Word files are only as accessible as their authors made them.
- The LaTeX code editor works with screen readers but can be awkward, so a plain text box can replace
  it.
- Theme and font preferences can be changed in a copy of the site. If a change would help others,
  please open an issue or pull request.

## Feedback wanted

Accessibility problems are treated as bugs. If something gets in the way, open an
[accessibility issue](https://github.com/zaccesss/vitafolio/issues/new?template=accessibility.yml)
describing what happened and what would work better.

## The shared statement

> [!NOTE]
> The shared accessibility statement is in [zaccesss/accessibility](https://github.com/zaccesss/accessibility)
> and on [isaacadjei.me](https://isaacadjei.me/accessibility). This file takes precedence where the two
> differ.
