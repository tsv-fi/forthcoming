# Forthcoming articles plugin for OJS 3.5

Publish articles ahead of print. Forthcoming articles get their own landing pages, DOIs and usage statistics, and they keep the same URL when they are later published in a regular issue. The plugin uses OJS publication versioning to move an article from the Forthcoming listing into its final issue.

## Setup

1. Create **one** issue, name it for example "Forthcoming", and *publish the issue*. Keep using this same issue permanently; do not create a new Forthcoming issue for each volume. Do not mark it as the current issue.
2. Enable the plugin under Settings → Website → Plugins.
3. Open the plugin settings and do the following:
   - Choose the Forthcoming issue. Only published issues of the journal are listed.
   - Choose the order of the listing: oldest first or newest first, by publication date.
4. Add the listing page to your menu under Settings → Website → Setup → Navigation: create a menu item and choose "Forthcoming" as its type.

## What the plugin does

- Lists all articles in the Forthcoming issue on the `/forthcoming` page, with authors, publication dates and galleys.
- Redirects the Forthcoming issue's table of contents page to the listing page.
- Hides the Forthcoming issue from the public issue archive.
- Shows a "Forthcoming" label on the article landing page. Themes can restyle it with the `.forthcomingLabel span` selector.
- Marks the Forthcoming issue with a star in the backend issue archive (Issues → Back Issues).

## Workflow

1. **Publishing ahead of print:** assign the article to the Forthcoming issue in its publication metadata, then publish it. It now appears on the Forthcoming page.
2. **Moving it to its final issue:** *create a new version* of the article, change the issue, and publish the new version. The article stays on the Forthcoming page until that new version is published, then disappears from it.
   If the final issue is not published yet, the new version is only scheduled. The article stays on the Forthcoming page until that issue is published.

**DOIs:** The article keeps the same DOI in all versions. If the DOI was already deposited while the article was in the Forthcoming issue, publishing the new version marks it as "Needs Sync" on the DOIs page. Deposit it again so the registration agency gets the final issue details. Avoid DOI patterns that include issue details, because the DOI is generated while the article is still in the Forthcoming issue and does not change when it moves.

[![Plugin created by The Federation of Finnish Learned Societies](https://img.shields.io/badge/Plugin%20created%20by-The%20Federation%20of%20Finnish%20Learned%20Societies-5A2A82?style=for-the-badge&labelColor=4B196E&logoColor=white)](https://tsv.fi/en/)
