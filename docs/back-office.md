---
title: The back office
order: 38
---

# The back office

Loaded when omnibase/admin is installed. The screens keep omnibase/admin's default
permissions.

| CRUD | Slug | Fields and actions |
|---|---|---|
| `ReleaseCrudController` | `releases` | the sleeve, title, state, slug, type, label, catalogue, UPC, label's page, date, plays (read), featured, announced and pre-save link, publication date, the catalogue's sleeve, performers, headline, excerpt, the tracks (a collection of `TrackType`), the links (`LinksType`: one address per platform, by rank, the label's and shop's left out), the prizes, the liner notes; filters state, label, featured, announced; **Complete** and **Waveforms** on a record, **Look up a record** on the list |
| `TrackType` (in a record) | - | disc, place, title or work and movement, length, ISRC, the file and "Whole track", the preview's address, performers, the day it can be heard, the countries it is not played in, plays (read) |
| `LabelCrudController` | `labels` | logo, name, slug, site, shop, record page pattern, country, description |
| `WorkCrudController` | `works` | composer, title, opus, formation, instrumentation, period, year, length, position, visible, movements |
| `VideoCrudController` | `videos` | poster, title, state, slug, publication date, YouTube or Vimeo address, the file, length, recorded on, venue, featured, work, record, performers, headline, excerpt; **Pick from the film** on a film |
| `InstrumentCrudController` | `instruments` | picture, name, kind, maker, place, year, model, lender, visible, featured, provenance, description |
| `PerformerCrudController` | `performers` | name, kind, role, address, position |
| `PlaylistCrudController` | `playlists` | title, slug, kind, address, position, visible, featured, description |

A record or a film created in the back office has the signed-in user as an owner. The routes
are omnibase/admin's `admin_crud_<slug>_*`; the actions' are `admin_crud_releases_complete`,
`admin_crud_releases_peaks`, `admin_crud_releases_lookup`, `admin_crud_videos_poster`.

What Complete and Look up do is in [The catalogues](usage-catalogues.md); Waveforms and the
poster in [Waveforms and posters](usage-media.md).

## The dashboard tile

`Base\Music\Admin\Widget\LatestReleaseWidgetType`, named `music_latest`
(`@Music/admin/widget/latest.html.twig`): the last record out and the next one announced, each
linking to its record; the plays of every track together; and how many tracks the player has
nothing to play for (no file, no preview), highlighted when there are some.

```php
// App\Controller\Admin\DashboardController::configureWidgetItems()
yield MenuItem::block('music_latest', 'Musique', 'fa-solid fa-record-vinyl');
```

## Settings

The home page's film is set in the back office's settings: `music.hero.video` and
`music.hero.poster` (an upload or an address); `music.hero.position` and `music.hero.zoom` are
written by the reframing tool on the page.
