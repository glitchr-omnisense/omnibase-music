---
title: The catalogues
order: 32
---

# The catalogues

The links to every platform, the previews and the platforms' players come from
omnisense/omnisong: song.link (Odesli) for the links, the iTunes Search API for the tracks and
their 30-second previews, and the iframes of Spotify, Apple Music, Deezer, YouTube and
SoundCloud. Omnisong's bundle registers `Omnisong\Catalog\Catalog`,
`Omnisong\Player\EmbedderInterface` and `Omnisong\Player\EmbedOptions`; this bundle asks
them, each optional.

`music.country` (`DE`) is the store the catalogues are asked about and the one the links open.

## Completing a record

`Base\Music\Service\Importer::complete(Release $release, ?Reference $reference = null): ImportReport`
asks the catalogues what they know of a record, by `$reference`, else its UPC, else its first
platform link (not the label's, shop's or "other" ones):

- Only what is empty is filled: the title, the UPC, the date, the sleeve (`coverUrl`, a remote
  address, unless a sleeve is uploaded or a `coverUrl` is set), the label (an existing
  `Label` of that name whatever its case, or a new one with the catalogue's site and shop),
  the links of the platforms the record has none for.
- The label's own links never come from a catalogue: they are the `Label`'s (and `labelUrl`).
- The tracks are matched by their ISRC, else by their disc and place (unless the track there
  is another recording, or was matched already); a matched track gets the title (when it has
  neither a title nor a work), the duration, the ISRC and the preview it lacks; an unmatched
  one is created.
- When no catalogue knows the record itself, the links alone are filled.

Nothing is flushed. `\InvalidArgumentException` when the record has neither a UPC nor a link
and no reference is given; `\LogicException` without Omnisong's bundle; what a catalogue
throws beyond being down is an `Omnisong\Exception\OmnisongException`.

`ImportReport`: `found`, `filled` (the field names, `links.<platform>` for the links),
`tracksCreated`, `tracksUpdated`, `incomplete` (the catalogues that did not answer: ask again
later). `isUnavailable()` - nothing found because a catalogue was down;
`changedSomething()`.

## Looking a record up

`Importer::import(Reference $reference, ?ImportReport &$report = null): ?Release` makes a
record from one URL (any platform) or one UPC: the record of the site that already has the
UPC, completed, or a new draft titled and typed as the catalogue says
(`ReleaseType::fromCatalogue()`), persisted and not flushed. `null` when no catalogue knows
it; `$report` says whether one was down.

`Importer::reference(string $value)` reads a loose string (a URL, a UPC or EAN, an ISRC)
with Omnisong's `Reference::parse()`, for `music.country`.

```php
use Base\Music\Service\Importer;

$release = $importer->import($importer->reference('https://open.spotify.com/album/...'), $report);
if (null !== $release) {
    $entityManager->flush();
}
```

## In the back office

A record's **Complete** (`/admin/releases/{entityId}/complete`) and **Look up a record**
(`/admin/releases/lookup`, a form with one field `reference`) run these and say what they
did in flash messages: the fields filled and the tracks created or updated, "nothing new",
"not found", or which catalogue did not answer. A catalogue that does not answer is not
"not found": try again later.

## The label first

`Release::getPlatformLinks()` puts the label in front - the record's page at the label
(`labelUrl`, else the label's `releaseUrlPattern` filled with `{catalogue}`, `{upc}` or
`{slug}`, else the label's site), then the label's shop - and the platforms after, by
Omnisong's weight. `music_links(release)` draws them in that order; with
`music.label_first: false`, the template gets `label_first` false.
