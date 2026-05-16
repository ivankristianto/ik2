# Blog UI Kit — ivankristianto.com

Hi-fi recreation of the proposed blog redesign described in the main README. This is **the** product in the design system — Ivan's personal blog.

## What's in here

| File                  | Purpose                                                              |
| :-------------------- | :------------------------------------------------------------------- |
| `index.html`          | Click-through prototype — Home, Guides, Article, Resume              |
| `Header.jsx`          | Wordmark + nav + Resume button                                       |
| `Hero.jsx`            | Homepage hero — mono eyebrow + big title + CTA stack                 |
| `ArticleCard.jsx`     | Standard post card with image, title, meta, tags                     |
| `GuideCard.jsx`       | Multi-part guide card — eyebrow + title + description                |
| `NoteRow.jsx`         | Two-column date + title row used in archives                         |
| `TagPill.jsx`         | Soft blue tag chip used inline anywhere                              |
| `Article.jsx`         | Single-post layout — title, meta, content rhythm                     |
| `Callout.jsx`         | Note / Outdated / Updated callouts                                   |
| `Resume.jsx`          | Resume sections + skill rows                                         |
| `Footer.jsx`          | Soft Paper footer with social + RSS                                  |
| `mockData.js`         | Sample posts, guides, notes, resume data                             |

## Components are cosmetic

These re-implement the visual layer only. Navigation between screens is fake — clicking nav links swaps a local React state, not real routing. The point is pixel-faithful UI, not production code.

## Running

Open `index.html` directly — React + Babel are pinned via UMD CDN. No build step.
