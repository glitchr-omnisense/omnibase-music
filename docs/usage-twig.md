---
title: In a host page
order: 35
---

# In a host page

## Twig functions

`Base\Music\Twig\MusicExtension`, autoconfigured. The functions that render return safe HTML.

| Function | Returns |
|---|---|
| `music_player_bar()` | the player's bar, once in the layout (it loads `player.js`) |
| `music_player(release)` | "Listen in full" on the platforms, then the tracks with their play buttons and waveforms; a platform player when no track has audio and `embed` is allowed |
| `music_player(release, {fold: true})` | the same for a page showing several records: one "listen" button (`[data-music-release-play]`), the tracks behind a disclosure |
| `music_player(track)` | one track's button |
| `music_links(release)` | the label block, then the platforms as pills |
| `music_full(release)` | "Listen in full on ..." alone |
| `music_embed(release, platform = null)`, `music_listen_embed(release, platform = null)` | one platform's player of the record (or the first that plays), behind a click; empty when none |
| `music_playlist_embed(playlist)` | a playlist's player, behind a click; empty when its platform cannot be embedded |
| `music_hero_video(attributes = {})` | the home page's film ([The player](usage-player.md#the-hero-film)) |
| `music_latest(limit = 3, type = null)` | the last records out; `'album'` leaves the singles out |
| `music_releases()`, `music_featured()`, `music_upcoming()` | the discography, the featured record, the announced ones |
| `music_videos(limit = null)` | the published films |
| `music_playlists(kind = null)` | the visible playlists, of a kind (`own`, `recommended`, `artist`) |
| `music_instrument()` | the featured instrument |
| `music_performers(release \| video)` | "Guillaume Vincent, piano · Ensemble Sur Le Pont" - the template puts "with" before it |
| `music_platform_icon(platform)` | Font Awesome classes: the brand's mark where Font Awesome Free has one (Spotify, Apple, YouTube, Deezer, Amazon, Bandcamp, SoundCloud, Napster), a bag for the shop, a disc for the label, Presto and HIGHRESAUDIO, a note otherwise |
| `music_source(track)` | the track's source `{kind, src, whole}`, `null` when nothing plays or it cannot be heard now or here |
| `music_unavailable(track)` | why it cannot be heard (`{reason: 'later', date}` or `{reason: 'region'}`), or `null` |
| `music_has_full(release)` | whether "Listen in full" has a platform for it (asked once per record) |

The filter `music_duration` writes seconds as a sleeve prints them: `{{ 247|music_duration }}`
is `4:07`, `{{ 3765|music_duration }}` `1:02:45`, empty for `null`.

A home page:

```twig
{% set record = music_featured() %}
{% if record %}
    <h2>{{ record.title }}</h2>
    {{ music_links(record) }}
    {{ music_player(record, {fold: true}) }}
{% endif %}

{% for video in music_videos(3) %}
    <a href="{{ path('music_video', {slug: video.slug}) }}">{{ video.title }}</a>
{% endfor %}
```

## Templates

`@Music/client/`: `index`, `release`, `repertoire`, `videos`, `video`, `instrument` (the pages),
and the partials `_player`, `_player_bar`, `_track`, `_tracks`, `_links`, `_label`, `_full`,
`_embed`, `_hero_video`, `_release_card`, `_video_card`, `_upcoming`, `_sheet_about`.
`@Music/admin/`: `widget/latest`, `release/lookup`. An application overrides one in
`templates/bundles/MusicBundle/...`.

## The stylesheet

`bundles/music/css/music.css` reads the host's custom properties: `--music-accent`,
`--music-bg`, `--music-fg`, `--music-soft`, `--music-line`, `--music-surface`,
`--music-measure`, `--music-bar-bg`, and for the waveforms (read by `player.js`)
`--music-wave` and `--music-wave-played`.

## Translations

The domains `music` (the pages, the player, the back office, the consent switch:
`consent.label`, `consent.description`) and `entities` (the entities' names), ICU, in English,
French and German (`translations/*+intl-icu.{en,fr,de}.yaml`).
