# omnibase/music

A musician's site on [omnisense/omnibase](https://github.com/glitchr-omnisense/omnibase): the
records and the label that released them, the repertoire, the films, the instrument, the
people who play along - and one player for the whole page, with no JavaScript dependency. It
is a Symfony bundle (`Base\Music\MusicBundle`) on the core 3 or 4, with its back office on
omnibase/admin and its catalogues through
[omnisense/omnisong](https://github.com/glitchr-omnisense/omnisong).

It was written first for two classical musicians - a harpist whose albums came out on
ES-DUR, a violinist whose sonatas came out on FARAO classics - and keeps one rule of theirs:
**the label goes first**. Its logo beside the sleeve, "released by", "buy it at the label" as
the first button, and only then the streaming platforms (`music.label_first`).

What it adds:

- **Discography.** A `Release` is an omnibase `Thread` (a JOINED subclass, table
  `music_release`): title, headline, excerpt, liner notes, slug, publish states and
  translations come from the thread; the release adds its type, its `Label` and catalogue
  number, its UPC, its date, its sleeve, its `Track`s, its performers, its prizes and its
  links per platform.
- **Repertoire, films, instrument, performers, playlists.** `Work`, `Video` (a `Thread` too),
  `Instrument`, `Performer`, `Playlist`, each with its page or its Twig function.
- **One player.** One `<audio>` element for the page, a bar the layout includes once, static
  waveforms computed on the server with ffmpeg, "Listen in full" on Spotify, Apple Music or
  Deezer on a click, plays counted without keeping anything about the listener.
- **The catalogues.** "Complete" and "Look up" in the back office fill a record from
  song.link and iTunes through omnisense/omnisong - only what is empty.

Use it for a performer's or a small label's site. It is not a streaming service: a track
plays the site's own excerpt (or whole file, when its rights allow), a catalogue's 30-second
preview, or a platform's player.

## How this manual is organised

- [Installation](installation.md): requirements, the bundles, the routes, the schema, the
  public files, what the layout provides.
- [Records, works and films](concepts.md): every entity, its fields and methods, the enums,
  the repositories.
- [The pages](usage-pages.md): every route, what each page shows, the JSON-LD, when a track
  can be heard.
- [The player](usage-player.md): sources, the bar, "Listen in full", consent, plays, the hero
  film, the JavaScript API and events.
- [The catalogues](usage-catalogues.md): Omnisong, Complete, Look up, the label's links.
- [Waveforms and posters](usage-media.md): `music:peaks`, `music:posters`, ffmpeg.
- [In a host page](usage-twig.md): the Twig functions and filter, the templates, the
  stylesheet's custom properties, the translations.
- [The back office](back-office.md): the screens, their actions, the dashboard tile.
- [Configuration reference](configuration.md): `music:`, the parameters, the settings, the
  services.
- [Testing](testing.md): the suite and what it checks.
