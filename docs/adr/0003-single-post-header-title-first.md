# Single post header: category trail, title first, then the image

The single post header was cluttered on phones. The breadcrumb ended with the full post title, which Yoast set in bold, right above the same title as the H1. The meta row mixed five items with a category pill, and when it wrapped, the next line started with a `·`. The header now runs: a lowercase mono trail of section and primary category (`articles / performance`, or `speaking` alone for talks), the H1, date · reading time · byline on one line, `updated <date>` on a second line only when the post changed on a later day, then the featured image. The trail is theme markup. Yoast no longer renders anything visible, but it still prints the full BreadcrumbList schema through `insert_parent_crumb()`.

The title stays above the featured image. PRODUCT.md says most readers land on a post from search, where they saw only the title, so the title is what tells them they are on the right page. That share of search traffic is the brief's claim, not a measured number. Eye-tracking research from Nielsen Norman Group gives the rest: attention is heavy on the first screen (57% of viewing time in their 2018 study), and people look at informative images and skip decorative ones. Feature cards are informative, so above the title a card would take the first look and push the title to second.

## Considered Options

- **A. Keep the elements, stack the meta.** Trail without the title, pill on its own row, meta in two fixed rows. The smallest change, but it leaves three rows of metadata on a phone.
- **B1. Category as the trail, title then image (chosen).** Removes the pill and the duplicate title, and fits the meta on one line at 390px.
- **B2. Same as B1, image before the title.** Matches the order of social link previews (card, then title). But the card's punch line is written never to repeat the title, so readers meet two different headlines before the body. Posts without a featured image would also start at a different height from post to post.
- **C. Notebook record.** Meta as a ruled two-by-two `<dl>`. Has the most character, but it's the most markup, and posts that were never updated leave a hole in it.

## Consequences

If Search Console and GA4 show that most visits come from shared links rather than search, revisit B2. Separator dots trail the item before them (`:not(:last-child)::after`), so below about 340px, or at a larger text size, line one wraps and ends on a dot. Never put the dot at the start of an item. Talks drop the category from the trail, because the `talk` category is what puts them under Speaking.
