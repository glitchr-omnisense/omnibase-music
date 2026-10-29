---
title: The player
order: 31
---

# The player

One `<audio>` element for the whole page, driven by `bundles/music/js/player.js`, in the bar
the layout includes once (`{{ music_player_bar() }}`). The pages' play buttons
(`[data-music-track]`) hand their track to it.

## What a track plays from

`Base\Music\Service\Player::source(Track $track): ?array` goes through
`music.player.order` (`[file, preview, embed]` by default) and returns the first source found,
`{kind, src, whole}`:

| Kind | Source |
|---|---|
| `file` | the site's own file (`Track::getSampleUrl()`); `whole` is true when the track is ticked "Whole track" |
| `preview` | the catalogue's 30-second preview (`previewUrl`) |
| `embed` | the iframe of the record's first platform link Omnisong can embed, loaded on a click |

`null` when there is nothing. Twig's `music_source()` also returns `null` for a track that
cannot be heard now or here ([The pages](usage-pages.md#when-a-track-can-be-heard)).
Removing `embed` from `music.player.order` keeps platform players out of the track list
(`Player::embedsAllowed()`); "Listen in full" stays.

## The bar

It shows what plays, play and pause, the previous and next track of the same record, the
progress on the waveform, the time and the volume; it can be tucked into a small handle and
expanded into a sheet about the record. **Space** plays or pauses, the **arrows** seek five
seconds, **Shift+arrows** change track. Its cross (`[data-music-close]`) stops the music and
hides the bar; playing anything brings it back. **Escape**, with the focus in the bar, folds
the sheet, then tucks the bar into its handle - the music goes on.

On `<html>`: `music-is-playing` while it sounds, `music-playing-bar` while the bar shows.
The bar's `data-music-waves` is `off`, `static` or `live` (from `music.player.waves` and
`live_waves`).

### Waveforms

The waveform is static: the track's `peaks` (200 values, computed once on the server -
[Waveforms and posters](usage-media.md)) drawn on a `<canvas>`, the played part in the
custom property `--music-wave-played` (`currentColor` by default), the rest in `--music-wave`
(`rgba(127,127,127,.45)`). Without peaks, a plain line. With `music.player.live_waves`, the
bar's canvas follows the sound through an `AnalyserNode`, unless the visitor asks for less
motion. A preview from another site goes through Web Audio only when that site answers with
CORS; otherwise it plays on a plain element, without the live wave.

## Listen in full

A 30-second preview is a taste; a play on a platform by a listener signed in there is a
stream, counted for the musician. Beside the previews, on a record's page and in the bar,
"Listen in full on Spotify / Apple Music / Deezer" swaps in the platform's own player of the
whole record. `music.player.full` says which platforms, in which order (Omnisong's `Platform`
values); a record offers those it has a link on and Omnisong can embed
(`Player::full()`, `Player::hasFull()`). Nothing loads from a platform before the click; the
bar asks `/music/{slug}/full` for the iframes.

The platforms' players are built with Omnisong's `EmbedOptions` (its bundle's), on the theme
`music.player.theme`.

### The whole track on the site

A track plays in full from the site's own file when the file is uploaded on it (64 MB at
most) and "Whole track" is ticked - unticked by default. The bar then offers no "Listen in
full" for it.

> [!IMPORTANT]
> Playing a recording in full on the site presumes the agreement of whoever holds its rights
> (the label, the publisher). An excerpt stays the default.

### Consent

Spotify, Apple Music, Deezer, YouTube and Vimeo set their own cookies. With omnibase/consent
on the page (`window.Consent`), a platform player - "Listen in full", the bar's sheet, a film
from a platform - loads only once the visitor accepts the feature `MEDIA`: asked on the click
that wants it, played as soon as they say yes, never on a hover. `player.js` declares the
feature with the label and description of the translation keys `music.consent.label`
("Platforms' players") and `music.consent.description`. Without omnibase/consent, the players
load on the click.

## Plays

`player.js` posts `/music/play/{id}` once a track was heard - 30 seconds of it, or 80% of a
shorter file, counting only the time that sounded: a jump on the waveform is not listening. `Base\Music\Service\Plays::count()`
counts it unless:

- the user agent is a robot: omnibase's `Base\Service\Analytics\UserAgentClassifier` when the
  core has it (anything but `human`), else an empty user agent;
- the same visitor played the same track less than 20 seconds ago (`Plays::EVERY`), keyed by
  a hash of the address and the track in the cache pool, gone after those 20 seconds.

The controller does not count users with `ROLE_STAFF`. `TrackRepository::countPlay()` adds
one in the database itself, so two listeners at once are two plays. Nothing about the
listener is stored. What plays in a platform's own player is counted there.

## The hero film

`{{ music_hero_video() }}` on the home page: a film muted, looping, playing by itself
(`autoplay muted loop playsinline`), a button to hear it, its poster alone for a visitor who
asks for less motion. The film and the poster are two settings, `music.hero.video` and
`music.hero.poster` (an upload or an address); `music_hero_video({poster: '...'})` gives the
host's still until a poster is set. With neither, nothing is rendered.

For an administrator (`ROLE_ADMIN`) the film carries a "Reframe" tool
(`bundles/music/js/hero-frame.js`): drag the picture, zoom from 1 to 2.5, save - a POST to
`/admin/music/hero-frame` with the `X-CSRF-Token` header (token `music-hero-frame`) and
`{"position": "52% 31%", "zoom": 1.4}` or `{"reset": true}`. `Base\Music\Service\HeroFrame`
keeps them in the settings `music.hero.position` and `music.hero.zoom` (a zoom of 1 is
`null`); a position that is not `<x>% <y>%` or a zoom outside 1 to 2.5 is a 422 with the
message, a bad token a 403.

## JavaScript API

`player.js` exposes `window.MusicPlayer`:

| Member | |
|---|---|
| `audio` | the `<audio>` element |
| `analyser` | the `AnalyserNode`, made on the first play a visitor asked for (even without `live_waves`); `null` before, or without Web Audio |
| `context` | its `AudioContext` |
| `current` | the current track `{src, title, release, peaks}`, or `null` |
| `play(el \| data)` | plays a `[data-music-track]` element, or `{src, title, release, peaks}` |
| `pause()`, `toggle()`, `seek(seconds)`, `next()`, `prev()` | |
| `playRelease(slug)` | a record's first track, then pause and resume while one of its tracks is current |
| `close()` | stops the music and hides the bar |
| `tuck()`, `untuck()`, `expand(open?)` | the bar's handle and sheet |
| `full(release, platform?)`, `listenInFull(platform)` | "Listen in full" for a record (its slug), for the current record |
| `redraw()` | draws the waveforms again after the page changed |

Events, dispatched on `document` as `CustomEvent`s:

| Event | `detail` |
|---|---|
| `music:track` | the track that starts |
| `music:play`, `music:pause`, `music:ended` | the current track |
| `music:time` | `{currentTime, duration}`, about four times a second |
| `music:full` | `{release, platform}`: a platform's player replaced the preview |
| `music:film` | `{title}`: a film started |
| `music:tuck`, `music:untuck` | the current track |
| `music:close` | the track that was playing |

```js
document.addEventListener('music:play', () => {
    const { analyser } = window.MusicPlayer;
    if (!analyser) return;
    const data = new Uint8Array(analyser.frequencyBinCount);
    (function frame() { analyser.getByteFrequencyData(data); /* draw */ requestAnimationFrame(frame); })();
});
```
