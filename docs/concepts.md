---
title: Records, works and films
order: 20
---

# Records, works and films

A `Release` is an omnibase thread (discriminator `music`); the others are plain rows. Uploads
(`#[Uploader]`: a sleeve, a logo, an excerpt, a film, a poster, a picture) are stored by the
core's uploader; their getters return the public path, and `get…File()` the file.

## `Release`

`Base\Music\Entity\Release` extends `Base\Entity\Thread` (table `music_release`,
discriminator `music_release`). From the thread: `title`, `headline`, `excerpt`, `content`
(the liner notes), `slug`, `state`, `publishedAt`, the translations. Its own columns:

| Property | Column | Default | Meaning |
|---|---|---|---|
| `type` | `ReleaseType`, `string(16)` | `ALBUM` | album, single, EP, live, compilation |
| `label` | many-to-one `Label`, `SET NULL` | `null` | who released it |
| `labelUrl` | `string(255)`, nullable | | the record's own page at the label; wins over the label's pattern |
| `catalogue` | `string(64)`, nullable | | its number at the label |
| `upc` | `string(14)`, nullable | | digits only (`setUpc()` drops the rest) |
| `releasedAt` | `date`, nullable | | |
| `upcoming` | `boolean` | `false` | announced, not out |
| `presaveUrl` | `string(255)`, nullable | | the pre-save link while it is announced |
| `cover` | upload, image, 16 MB | | the sleeve |
| `coverUrl` | `string(500)`, nullable | | a catalogue's sleeve, a remote address, until one is uploaded |
| `links` | `json`, nullable | | platform value => URL (`spotify`, `apple_music`, `qobuz`...) |
| `awards` | `text`, nullable | | the prizes, one a line |
| `performers` | many-to-many `Performer` (`music_release_performer`) | | by position, then name |
| `tracks` | one-to-many `Track`, cascade persist and remove, orphan removal | | by disc, then position |
| `featured` | `boolean` | `false` | the record `/music` opens on |

Methods:

- `getArtwork()` - the sleeve's public address: the upload's path under `/public` (a path
  on disk is turned into its `/uploads/...` address), else `coverUrl`.
- `getPlatformLinks(): Omnisong\Model\PlatformLinks` - the links, with the label in front:
  the label's link is `labelUrl`, else the label's pattern for this record
  (`Label::releaseUrl()`), else the label's site; then its shop; then the platforms by
  weight. Without a label, `labelUrl` alone is the label link.
- `getLabelLink()` - the label's page of this record when there is one (`labelUrl` or the
  pattern), else the label's shop, else its site.
- `getAwardList()` - each line of `awards` as `{title, by}`, split on the first dash between
  spaces (`CD of the year — Radio Exemple`).
- `getOrderedTracks()`, `getDiscs()` (tracks by disc), `getPlays()` (the tracks' plays
  together), `getDuration()` (the sum, or `null` when a track has no length).
- `addTrack()` gives a track without a position the next one.
- `isOut()` - not announced, and its date (if any) is today or past.
- `publish(bool $published = true, ?\DateTimeInterface $at = null)` - published or draft;
  `publishedAt` set to `$at`, kept, or now.
- `setLink(Platform $platform, ?string $url)`; `setLinks()` drops empty addresses.

## `Track`

`music_track`, indexed on `isrc`.

| Property | Column | Default | Meaning |
|---|---|---|---|
| `release` | many-to-one, `CASCADE` | | |
| `disc` | `integer` | `1` | at least 1 |
| `position` | `integer`, nullable | | its place on the disc |
| `title` | `string(255)`, nullable | | the title typed; else the work and the movement show |
| `work` | many-to-one `Work`, `SET NULL` | | the work it is |
| `movement` | `string(255)`, nullable | | |
| `duration` | `integer`, nullable | | seconds; 0 or less is `null` |
| `isrc` | `string(12)`, nullable | | upper-case alphanumeric, cut to 12 |
| `previewUrl` | `string(500)`, nullable | | a catalogue's 30 s preview |
| `sample` | upload, audio, 64 MB | | the site's own file: an excerpt, or the whole track |
| `whole` | `boolean` | `false` | the file is the whole track |
| `plays` | `integer` | `0` | counted by the player |
| `peaks` | `json`, nullable | | the waveform: values between 0 and 1 |
| `performers` | `text`, nullable | | who plays on this track, as text |
| `availableFrom` | `date`, nullable | | the day it can be heard (a single out before its album) |
| `blockedIn` | `json`, nullable | | ISO country codes where it is not played |

Methods: `getDisplayTitle()` (the title, else `Work – movement`), `getDurationText()` and
`Track::formatDuration($seconds)` (`4:07`, `1:02:45`), `getSampleUrl()` (the file's
`/uploads/...` address), `hasSample()`, `isWhole()` (whole and a file), `hasAudio()` (a file
or a preview), `isOutOn(\DateTimeInterface $day)` (`availableFrom` when set, else its
release is out), `getOutDate()`, `isBlockedIn(?string $country)`. A new `sample` (or none)
forgets the peaks. `setBlockedIn()` keeps two-letter codes, upper-cased and sorted.

## `Label`

`music_label`: `name` (not blank), `slug` (unique, from the name), `logo` (upload, 4 MB),
`url`, `shopUrl`, `releaseUrlPattern`, `country` (ISO, upper-cased), `description`.

`releaseUrl(Release $release): ?string` fills the pattern
(`https://records.example/releases/{catalogue}`) with `{catalogue}`, `{upc}` or `{slug}`,
URL-encoded; `null` without a pattern, or when the record lacks a value the pattern asks for.

## `Work`

`music_work`, indexed on `composer`: `composer` and `title` (not blank), `opus`, `year`,
`instrumentation` ("harp and orchestra"), `formation` (`Formation`, `SOLO` by default),
`duration`, `movements` (one a line), `period` (`Period`, nullable), `visible` (`true`),
`position`. `getFullTitle()` is `title, opus`; `__toString()` is `composer: full title`;
`getComposerSortKey()` sorts by the last word of the name; `getMovementList()`.

## Films

The films are omnibase/video's `Base\Video\Entity\Video` (its sources: a file of the site's, transcoded, or a
YouTube or Vimeo frame; its poster, its duration; where and when it was filmed: `venue`, `recordedAt`). A
`Track` has none, one or several (`getVideos()`, `addVideo()`, `removeVideo()`; many-to-many owned by music,
`music_track_video`); the work played and the performers are the track's. `Release::getVideos()` gives those of
its tracks, in the tracks' order, each once (no relation of its own).

## `Instrument`, `Performer`, `Playlist`

`Instrument` (`music_instrument`): `name`, `kind`, `maker`, `year`, `place`, `model`,
`provenance` (whose hands it went through), `loan` (who lends it), `image` (16 MB),
`description`, `visible`, `featured`. `getOrigin()` is "Cremona, 1690".

`Performer` (`music_performer`): `name`, `kind` (`PerformerKind`), `role` ("piano"), `url`,
`position`. `__toString()` is "Paul Exemple, piano".

`Playlist` (`music_playlist`): `title`, `slug`, `url` (not blank, a URL with a TLD), `kind`
(`PlaylistKind`, `OWN` by default), `description`, `featured`, `position`, `visible`.
`getPlatform()` reads the platform off the address (Omnisong's `Platform::fromUrl()`, and
`music.apple.com` for an Apple Music playlist or artist); `getLink()` the `PlatformLink`.

## Enums

| Enum | Cases | Methods |
|---|---|---|
| `ReleaseType` | `ALBUM`, `SINGLE`, `EP`, `LIVE`, `COMPILATION` | `schema()` (schema.org's release type), `fromCatalogue(?string)` (`ALBUM` when unknown) |
| `Formation` | `SOLO`, `CHAMBER`, `CONCERTO`, `ORCHESTRA`, `VOCAL`, `OTHER` | `rank()`: solo, concerto, chamber, orchestra, vocal, other |
| `Period` | `BAROQUE`, `CLASSICAL`, `ROMANTIC`, `MODERN`, `CONTEMPORARY` | `rank()`, oldest first |
| `PerformerKind` | `PERSON`, `ENSEMBLE`, `ORCHESTRA`, `CONDUCTOR` | `schema()`: `Person` or `MusicGroup` |
| `PlaylistKind` | `OWN`, `RECOMMENDED`, `ARTIST` | `rank()`: artist, own, recommended |

## Repositories

| Repository | Methods |
|---|---|
| `ReleaseRepository` | `findPublished()` (published, `publishedAt` past, newest first - announced ones included), `findLatest(int $limit = 3, ?ReleaseType $type = null)` (out only), `findFeatured()` (the featured one out, else the newest out), `findOnePublished(string $slug)`, `findUpcoming()` (announced or dated in the future, the nearest first), `findVideos(?int $limit = null)` (the site's films: those of the published releases' tracks, newest release first, each once, the reachable ones) |
| `TrackRepository` | `countSilent()` (no file, no preview), `countPlay(Track)` (adds one in the database), `countPlays()`, `findWithSample(bool $onlyWithoutPeaks = false)`, `findOneByVideo(Video)` (the track a film is of) |
| `WorkRepository` | `findVisible()`, `findVisibleGrouped(string $by)` and `WorkRepository::group()`: group => composer => works, by `formation` (by rank), `composer` (by surname) or `period` (oldest first, no period last) |
| `InstrumentRepository` | `findVisible()` (featured first), `findFeatured()` (else the first visible) |
| `PlaylistRepository` | `findVisible(?PlaylistKind $kind = null)` (featured first, then by position), `findFeatured(?PlaylistKind $kind = null)` |
| `LabelRepository` | `findOneByName(string $name)`, whatever its case |
| `PerformerRepository` | the default queries |
