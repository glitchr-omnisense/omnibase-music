---
title: Configuration reference
order: 40
---

# Configuration reference

## `music:`

`Base\Music\DependencyInjection\MusicConfiguration`, dumped with Symfony's
`YamlReferenceDumper` in omnisense/omnibase's harness image:

```yaml
music:
    player:

        # The static waveform of a track (its peaks) drawn behind the progress.
        waves:                true

        # The bar's wave moves with the sound (Web Audio); off, or for a visitor who asks for less motion, the static one stays.
        live_waves:           true

        # What a track plays from, first found first: the site's own file, a catalogue's 30 s preview, a platform's embed.
        order:

            # Defaults:
            - file
            - preview
            - embed

        # The platforms "Listen in full" offers for a release, in this order, when it has a link there (Omnisong\Platform values).
        full:

            # Defaults:
            - spotify
            - apple_music
            - deezer

        # The theme the platforms' embeds are asked for.
        theme:                light # One of "light"; "dark"

    # The musician's name, as the JSON-LD gives it (byArtist); null: the site's title (base.settings.title).
    artist:               null

    # The label - its logo, "released by", "buy it at the label" - before the streaming platforms.
    label_first:          true

    # ISO 3166-1 alpha-2: the store the catalogues are asked about, and the one the links open.
    country:              DE

    # A schema.org MusicAlbum on a release's page.
    jsonld:               true
    repertoire:

        # The /repertoire page.
        enabled:              true

        # How the works are grouped when the visitor has not chosen.
        group_by:             formation # One of "formation"; "composer"; "period"
    instrument:

        # The /instrument page (a 404 as long as no instrument is visible).
        enabled:              true
```

| Option | Type | Default | Read by | Effect |
|---|---|---|---|---|
| `player.waves` | bool | `true` | `MusicExtension` (Twig) | draw the static waveform behind the progress |
| `player.live_waves` | bool | `true` | `MusicExtension` | the bar's wave follows the sound |
| `player.order` | list of `file`, `preview`, `embed` | all three | `Player` | what a track plays from, first found first |
| `player.full` | list of Omnisong `Platform` values | `spotify`, `apple_music`, `deezer` | `Player` | the platforms of "Listen in full", in order; an unknown value is skipped |
| `player.theme` | `light` or `dark` | `light` | `Player` | the platforms' players' theme |
| `artist` | string or null | `null` | `JsonLd` | `byArtist`; `null` reads `base.settings.title` |
| `label_first` | bool | `true` | `MusicExtension` | given to `_links.html.twig` |
| `country` | string | `DE` | `Importer` | the catalogues' store |
| `jsonld` | bool | `true` | `MusicController` | the `MusicAlbum` on a record's page |
| `repertoire.enabled` | bool | `true` | `MusicController` | `/repertoire`, or a 404 |
| `repertoire.group_by` | `formation`, `composer`, `period` | `formation` | `MusicController` | the default grouping |
| `instrument.enabled` | bool | `true` | `MusicController` | `/instrument`, or a 404 |

## Parameters

One container parameter per key and per level (the core's
`AbstractBaseExtension::setConfiguration()`): `music.player` and each `music.player.*`,
`music.artist`, `music.label_first`, `music.country`, `music.jsonld`, `music.repertoire`,
`music.repertoire.enabled`, `music.repertoire.group_by`, `music.instrument`,
`music.instrument.enabled`.

## Settings

Kept in the core's setting bag, not in the configuration:

| Setting | Written by | Read by |
|---|---|---|
| `music.hero.video` | the back office's settings | `music_hero_video()` |
| `music.hero.poster` | the back office's settings | `music_hero_video()` |
| `music.hero.position` | `HeroFrame` (the reframing tool) | `music_hero_video()`, kept only when it is `<x>% <y>%` |
| `music.hero.zoom` | `HeroFrame` | `music_hero_video()`, kept only from 1 to 2.5 |
| `base.settings.title` | the core | `JsonLd::artist()` when `music.artist` is null |

## Services

`config/services.php` autowires and autoconfigures every class of `src/` but
`DependencyInjection/`, `Entity/`, `Enum/`, `Controller/Admin/`, `Admin/`,
`Service/ImportReport.php` and the bundle class; `Controller/Client/` is tagged
`controller.service_arguments`. When the core has `Base\Service\Analytics\UserAgentClassifier`,
`Plays` gets it as `$classifier` (`nullOnInvalid`). With omnibase/admin, `Controller/Admin/`
(tagged `controller.service_arguments`) and `Admin/` (the widget type) are loaded.

| Service | Optional collaborators |
|---|---|
| `Player` | `Omnisong\Player\EmbedderInterface`, `Omnisong\Player\EmbedOptions`, a logger |
| `Importer` | `Omnisong\Catalog\Catalog` |
| `Plays` | the core's user-agent classifier; the cache pool (`CacheItemPoolInterface`) is required |
| `Peaks`, `PosterPicker` | a logger; `PosterPicker` an HTTP client; both take the ffmpeg binary as `$ffmpeg` (`ffmpeg`, a service argument to set another path) |
| `JsonLd`, `MusicExtension` | the core's `SettingBagInterface` |
| `Availability` | the `RequestStack` |
