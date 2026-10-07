// the cv and cover letter pdf layout. every piece of text arrives through data.json and is placed
// as a plain string, never evaluated as markup, so nothing a user writes can run as typst code.
// typst tags the output (headings, paragraphs, lists, tables, links and figures) and the
// renderer compiles it to pdf/ua-1, so the file reads in screen readers the same way the web page does
#let data = json("data.json")
#let accent = rgb(data.accent)
#let ink = rgb("#141414")
#let muted = rgb("#55524c")
#let line-colour = rgb("#d8d4cc")
#let band = data.theme == "modern"
#let plain = data.theme == "plain"
#let minimal = data.theme == "minimal"
#let link-colour = if plain { ink } else { accent }
#let right-to-left = data.dir == "rtl"
// letter spacing and capitals only suit latin script; arabic, urdu and chinese headings keep their own shapes
#let latin-heading = data.lang not in ("ar", "ur", "zh")
// the chosen latin face covers latin, greek and cyrillic; every other script falls through to the
// noto faces in the order the cv's language prefers, then back to the latin face for symbols
#let latin = regex("[\u{0}-\u{5FF}\u{1D00}-\u{1EFF}\u{2000}-\u{20CF}\u{2100}-\u{218F}]")
#let faces(primary) = ((name: primary, covers: latin), ..data.script_fonts, primary)

#set document(title: data.title, author: data.name, keywords: data.keywords)
#set text(font: faces(data.font), size: 10pt, fill: ink, lang: data.lang, region: data.region, dir: if right-to-left { rtl } else { ltr })
// nastaliq rises and falls far more than latin letters and naskh a little more, so their lines sit further apart
#set par(leading: if data.lang == "ur" { 0.6em } else if data.lang == "ar" { 0.8em } else { 0.55em }, spacing: 0.75em)
// for urdu, lines are measured by the glyphs themselves, so tall letters never touch the line above or a heading rule
#show: body => if data.lang == "ur" { set text(top-edge: "bounds", bottom-edge: "bounds"); body } else { body }
#set page(
  paper: "a4",
  margin: (top: 14mm, bottom: 18mm, left: 16mm, right: 16mm),
  // page furniture: typst marks headers and footers as artifacts, so screen readers skip them
  footer: context align(center, text(size: 8pt, fill: rgb("#8a8375"))[
    #data.name #sym.dot.c #text(dir: ltr, data.address) #sym.dot.c #data.labels.page.replace(":current", str(counter(page).get().first())).replace(":total", str(counter(page).final().first()))
  ]),
)
#show link: set text(fill: link-colour)

// the name is the only level one heading, the sections sit below it and projects below those,
// which gives the pdf bookmarks the same outline as the web page
#show heading.where(level: 1): it => text(size: 22pt, weight: "bold", fill: if band { white } else { ink }, it.body)
#show heading.where(level: 2): it => {
  if minimal {
    block(above: 14pt, below: 6pt, text(font: faces("DejaVu Serif"), size: 13pt, weight: "regular", it.body))
  } else {
    block(
      above: 14pt, below: 7pt, width: 100%, inset: (bottom: 3pt), stroke: (bottom: 1pt + line-colour),
      text(size: 9pt, weight: "bold", tracking: if latin-heading { 1.5pt } else { 0pt }, fill: if plain { ink } else { accent }, if latin-heading { upper(it.body) } else { it.body }),
    )
  }
}
#show heading.where(level: 3): it => block(above: 8pt, below: 3pt, text(size: 10pt, weight: "bold", it.body))

// a block of user text: paragraphs, with "- " lines gathered into real lists by the renderer
#let blocks(items) = for item in items {
  if item.type == "list" {
    list(..item.items.map(entry => [#entry]))
  } else {
    par(item.lines.map(line => [#line]).join(linebreak()))
  }
}

#let short(url) = url.replace(regex("^https?://(www\.)?"), "").trim("/", at: end)

#let header-text = {
  let tone = if band { white } else { muted }
  heading(level: 1, data.name)
  if data.headline != none { v(2pt); text(size: 12pt, fill: tone, data.headline) }
  if data.details.len() > 0 { v(1pt); text(size: 9pt, fill: tone, data.details.join("  ·  ")) }
}

#let header-body = if data.photo != none and not plain {
  grid(
    columns: (30mm, 1fr), align: horizon,
    image("photo.png", width: 24mm, height: 24mm, alt: data.labels.photo),
    header-text,
  )
} else { header-text }

#if band {
  block(width: 100%, fill: accent, inset: (x: 12pt, y: 10pt), header-body)
} else {
  block(width: 100%, inset: (bottom: 8pt), stroke: (bottom: if plain { 1.5pt + ink } else { 0.75pt + line-colour }), header-body)
}
#v(4pt)

#for section in data.sections {
  heading(level: 2, section.heading)
  if section.kind == "text" {
    if section.at("note", default: none) != none { par(text(size: 9pt, fill: muted, section.note)) }
    blocks(section.blocks)
  } else if section.kind == "projects" {
    for project in section.projects {
      block(breakable: false, below: 7pt, {
        heading(level: 3, project.title)
        // printed in full, because a link in a printed cv is only useful if it can be typed
        if project.url != none { par(link(project.url, text(size: 9pt, dir: ltr, short(project.url)))) }
        blocks(project.blocks)
      })
    }
  } else if section.kind == "skills" {
    par(section.items.join("  ·  "))
  } else if section.kind == "links" {
    table(
      columns: (auto, 1fr), stroke: none, inset: (x: 0pt, y: 2pt), column-gutter: 10pt,
      table.header(text(size: 9pt, fill: muted, data.labels.site), text(size: 9pt, fill: muted, data.labels.address)),
      ..section.links.map(entry => (strong(entry.label), link(entry.url, text(dir: ltr, short(entry.url))))).flatten(),
    )
  }
}
