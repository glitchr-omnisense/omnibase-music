---
title: Installation
order: 10
---

# Installation

## Requirements

What `composer.json` requires: PHP 8.2 or later, `doctrine/orm` 2.15 or 3,
`omnisense/omnibase` 3.0 or 4.0, `omnibridge/symfony` 1.x, `omnibase/admin`,
`omnisense/omnisong` 1.x, and `symfony/config`, `dependency-injection`, `form`,
`http-kernel`, `security-bundle`, `process` and `twig-bundle` 6, 7 or 8.

On the server, ffmpeg computes the tracks' waveforms and reads the frames of the site's own
films (`apt install ffmpeg`, `apk add ffmpeg`); without it, both are skipped and said so
([Waveforms and posters](usage-media.md)). The poster picker needs PHP's GD extension.

## The bundles

```bash
composer require omnibase/music:dev-main
```

```php
// config/bundles.php
return [
    // ...
    Base\BaseBundle::class => ['all' => true],
    Base\Admin\AdminBundle::class => ['all' => true],
    Omnisong\Bridge\Symfony\OmnisongBundle::class => ['all' => true],
    Base\Music\MusicBundle::class => ['all' => true],
];
```

Omnisong's bundle registers `Omnisong\Catalog\Catalog`, `Omnisong\Player\EmbedderInterface`
and `Omnisong\Player\EmbedOptions`; this bundle only asks them, each optionally. Without
them, "Complete" and "Look up" say there is no catalogue, and no platform player is built.

`MusicBundle` extends the core's `AbstractBaseBundle`: `getPath()` is the package's root
(`templates/` is `@Music`, `public/` is installed as `bundles/music/`), and `build()` aliases
`Base\Music\Entity\*` to `App\Entity\Music\*` and `Base\Music\Repository\*` to
`App\Repository\Music\*` unless the application declares a class of that name - an
application declares `App\Entity\Music\Release extends Base\Music\Entity\Release` to add its
own fields.

## The routes

```yaml
# config/routes.yaml
music_controller:
    resource: "@MusicBundle/src/Controller/Client"
    type: attribute
    prefix: /
```

This imports the pages ([The pages](usage-pages.md)) and `music_hero_frame`, the
administrators' reframing of the home page's picture. The back office's routes come from
omnibase/admin's `base_admin` loader.

## Configuration

Every key is optional ([Configuration reference](configuration.md)):

```yaml
# config/packages/music.yaml
music:
    artist: null
    player:
        waves: true
        live_waves: true
        order: [file, preview, embed]
        full: [spotify, apple_music, deezer]
        theme: light
    label_first: true
    country: DE
    jsonld: true
    repertoire:
        enabled: true
        group_by: formation
    instrument:
        enabled: true
```

## The schema

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

Tables: `music_release` and `music_video` (joined to the core's thread table), `music_label`,
`music_track`, `music_work`, `music_instrument`, `music_performer`, `music_playlist`, and the
join tables `music_release_performer`, `music_video_performer`.

## The public files

```bash
bin/console assets:install
```

installs `bundles/music/css/music.css` (the pages' look), `bundles/music/js/player.js` (the
player) and `bundles/music/js/hero-frame.js` (the reframing tool, for administrators).

## What the layout provides

The pages extend `layout1.html.twig` and fill `title`, `description`, `content`,
`stylesheets` and `javascripts`: the site's layout defines them.

The player's bar belongs to the layout: once, before `</body>`,

```twig
{{ music_player_bar() }}
```

so the music goes on from page to page of a Turbo site and plays from the home page. The bar
loads `player.js` itself; the record, film and discography pages load it too.
