---
title: The pages
order: 30
---

# The pages

`Base\Music\Controller\Client\MusicController` and `HeroFrameController`:

| Route | Path | Methods | Sitemap | Page |
|---|---|---|---|---|
| `music_index` | `/music` | any | 0.9, weekly | the discography |
| `music_repertoire` | `/repertoire` | any | 0.6, monthly | the works |
| `music_videos` | `/videos` | any | 0.7, weekly | the films |
| `music_video` | `/videos/{slug}` | any | - | one film |
| `music_instrument` | `/instrument` | any | 0.5, yearly | the instrument |
| `music_release_full` | `/music/{slug}/full` | GET, JSON | - | what the bar needs to play a record in full |
| `music_track_play` | `/music/play/{id}` | POST | - | one play, told by the player |
| `music_release` | `/music/{slug}` | any | - | one record (declared last, so it never takes another page's path) |
| `music_hero_frame` | `/admin/music/hero-frame` | POST | - | the home picture's framing, `ROLE_ADMIN` |

Slugs are `[a-z0-9-]+`. A record or a film that is not published (or whose publication date
is still to come) is a 404.

## `/music`

`@Music/client/index.html.twig` with:

| Variable | Content |
|---|---|
| `featured` | `findFeatured()`: the featured record that is out, else the newest out |
| `upcoming` | the announced or future records, but the featured one |
| `others` | every other published record, newest first |
| `videos` | six films |
| `artist` | the featured `ARTIST` playlist (the musician's profile), first under "Listen in full" |
| `own`, `recommended` | the visible `OWN` and `RECOMMENDED` playlists |

## A record's page

`/music/{slug}` renders `@Music/client/release.html.twig`: the sleeve and the label (logo,
"released by", the label's page and shop before the platforms, as `music.label_first` says),
the player, the liner notes, the prizes, the performers, the films of the record
(`VideoRepository::findByRelease()`), and - with `music.jsonld` - its JSON-LD.

### JSON-LD

`Base\Music\Service\JsonLd::album(Release $release, ?string $url = null, ?string $image = null)`
gives schema.org's `MusicAlbum`: `name`, `url`, `image` (the artwork), `datePublished`,
`albumReleaseType` (`ReleaseType::schema()`), `albumProductionType` (`StudioAlbum`),
`catalogNumber`, `gtin` (the UPC), `description` (excerpt, else headline), `numTracks`,
`byArtist` (a `MusicGroup` named `music.artist`, else the site's title
`base.settings.title`), `contributor` (each performer as a `Person` or `MusicGroup`),
`recordLabel`, `track` (an `ItemList` of `MusicRecording`s: name, `PT<m>M<s>S` duration,
`isrcCode`, `recordingOf` the work and its composer), and `sameAs` (every platform link).
Empty values are left out.

## `/repertoire`

The visible works, grouped (`WorkRepository::findVisibleGrouped()`) by `?by=formation`,
`composer` or `period` - another value falls back to `music.repertoire.group_by`. In each
group, the works are by composer, sorted by surname. With `music.repertoire.enabled: false`
the page is a 404.

## `/videos` and a film's page

Every published film, featured first, then the newest. A film of the site's plays in place; a
YouTube or Vimeo film shows its poster and a play button first, and loads
`youtube-nocookie.com` or Vimeo with `dnt=1` only on the click (and, with omnibase/consent,
once `MEDIA` is accepted). A film's page shows four others.

## `/instrument`

The visible instruments, featured first: the first at length, the others after it. With no
visible instrument, or `music.instrument.enabled: false`, a 404.

## `/music/{slug}/full`

JSON for the player's bar, public, cached 300 seconds, `X-Robots-Tag: noindex`:

| Key | Content |
|---|---|
| `release` | the record's slug |
| `title` | its title |
| `page` | its absolute address on the site |
| `platforms` | a list of `{platform, label, html, src, height, page}` |
| `about` | the sheet's text about the record, as HTML |

`platforms` is `Player::listen()`: for each platform of `music.player.full` the record has a
link on and Omnisong can embed, the iframe and the record's page there (else the label's page
of it, else the site's). `about` is `@Music/client/_sheet_about.html.twig` rendered in the
reader's language.

## `/music/play/{id}`

A POST from `player.js` once a track was heard ([The player](usage-player.md#plays)): 204,
`X-Robots-Tag: noindex`. A track not on a published record is a 404; a user with
`ROLE_STAFF` is answered without counting.

## When a track can be heard

`Base\Music\Service\Availability` says whether a track plays here and now:

- not before its day: `Track::isOutOn(today)` - its `availableFrom`, else its record being
  out. `why()` gives `['reason' => 'later', 'date' => ...]`;
- not in a country of its `blockedIn`: `['reason' => 'region']`.

The visitor's country (`Availability::countryOf(Request)`) is the `country` cookie, else the
`CF-IPCountry` header, else `X-Country` (two letters; Cloudflare's `XX` and `T1` ignored),
else the region of the browser's first language (`de-CH` gives `CH`). None known: nothing is
withheld for a country. `music_source()` gives no source for such a track and
`music_unavailable()` says why ([In a host page](usage-twig.md)).

```php
use Base\Music\Entity\Track;
use Base\Music\Service\Availability;
use Symfony\Component\HttpFoundation\Request;

$track = (new Track())->setIsrc('de-a12-25-00001')->setBlockedIn(['us', 'CA', 'xx1']);
echo $track->getIsrc(), ' | ', implode(',', $track->getBlockedIn()), "\n";

$request = Request::create('/music', server: ['HTTP_CF_IPCOUNTRY' => 'us', 'HTTP_ACCEPT_LANGUAGE' => 'de-CH,de;q=0.9']);
var_dump(Availability::countryOf($request), $track->isBlockedIn(Availability::countryOf($request)));
var_dump(Availability::countryOf(Request::create('/music', server: ['HTTP_ACCEPT_LANGUAGE' => 'de-CH,de;q=0.9'])));
```

Run in omnisense/omnibase's harness image (with omnisense/omnisong on the autoloader):

```
DEA122500001 | CA,US
string(2) "US"
bool(true)
string(2) "CH"
```
