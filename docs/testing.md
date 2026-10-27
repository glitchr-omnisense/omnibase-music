---
title: Testing
order: 60
---

# Testing

## The suite

```bash
vendor/bin/phpunit                                                    # checked out on its own
php vendor/bin/phpunit -c vendor/omnibase/music/phpunit.xml.dist      # from an application that requires it
```

`tests/` runs without a kernel nor a database. `tests/bootstrap.php` registers this checkout's
classes and those of the omnibase/* packages it requires or suggests, read beside it; it does
not register omnisense/omnisong, which an application's autoloader has.

omnisense/omnibase's harness image does not install omnisense/omnisong: there, the suite needs
Omnisong's checkouts on the autoloader. With the omnisong workspace mounted under
`/fam/omnisong` and a prepended file that registers each `composer.json`'s PSR-4 namespaces
from `/fam/*/*/`:

```php
<?php
// prepend-families.php
$loader = require '/srv/demo/vendor/autoload.php';
foreach (glob('/fam/*/*/composer.json') ?: [] as $file) {
    $c = json_decode((string) file_get_contents($file), true) ?: [];
    foreach ($c['autoload']['psr-4'] ?? [] as $ns => $path) {
        if (!isset($loader->getPrefixesPsr4()[$ns])) {
            $loader->addPsr4($ns, dirname($file).'/'.trim((string) $path, './'));
        }
    }
}
```

```bash
docker run --rm --network none \
    -v "$PWD":/srv/demo/vendor/omnibase/music:ro \
    -v ~/Sites/omnisong:/fam/omnisong:ro \
    -v /path/to/scratch:/scratch:ro \
    --entrypoint sh omnisense/omnibase-harness -c \
    'cd /srv/demo && APP_ENV=test php -d auto_prepend_file=/scratch/prepend-families.php vendor/bin/phpunit -c vendor/omnibase/music/phpunit.xml.dist --do-not-cache-result'
```

```
...............................................................   63 / 63 (100%)

OK (63 tests, 1428 assertions)
```

Without Omnisong, the 16 tests that reach `Omnisong\Platform` fail with "Class not found".

## What it checks

| File | What |
|---|---|
| `Entity/ReleaseTest` | the label first at the record's page its pattern gives; the record's own page at the label wins; without what the pattern asks, the label's site; the pattern takes the UPC and the slug; "buy it at the label" opens the record's page, else the shop; without a label the links are the links; prizes one a line; tracks by disc then place and their total; out and announced |
| `Entity/ReleaseArtworkTest` | an uploaded sleeve is its address on the site; without one, the catalogue's |
| `Entity/TrackTest` | durations; the title falls back to the work and its movement; ISRC normalised; peaks clamped and forgotten with a new file; "whole" needs a file; disc 1 at least |
| `Entity/VideoTest` | the YouTube id from any address; embeds without tracking; a local film has no embed |
| `Entity/PlaylistTest` | the platform read off the address; the link carries the title |
| `Repository/WorkGroupTest` | by formation in their order and composers by surname; by composer; by period, oldest first, undated last; an unknown grouping is by formation |
| `Form/LinksTypeTest` | one URL field per platform in their rank, the label left out; submitted into the array the entity stores |
| `Service/AvailabilityTest` | an announced album plays its singles and keeps its other tracks for its day; a track of a record out plays unless its own day is ahead; a track withheld in a country is greyed there and only there |
| `Service/PeaksTest` | the loudest window is 1 and the others follow their RMS; a sine gives an even wave of 200 bars; negative samples read as signed; short, silent and empty buffers |
| `Service/PlayerSourceTest` | the site's file by its address, an excerpt by default; the whole track when ticked; without a file a catalogue's 30 s, never whole; no platform player, nothing to offer |
| `Service/PlaysTest` | one listening is one play; a robot is not a listener; without the core's classifier only an empty user agent is a robot; the visitor is kept nowhere |
| `Service/ImporterTest` | only empty fields are filled; a track matched by its ISRC first; a known label reused and a second run changes nothing |
| `Service/PosterPickerTest` | a lit, sharp, warm frame wins over a black or flat one; a letterbox's bars are cut off |
| `Twig/PlatformIconTest` | a brand's mark; a neutral icon for the others; every platform has one |
| `Twig/PlayerContractTest` | every platform player waits for the visitor's yes; the consent switch is named in each language; the drawer has its three rests |
| `Twig/WaveCanvasTest` | the templates give their canvas an id; the player's wave has one id either way |
| `Controller/CrudRequiredFieldsTest` | the CRUDs' required fields, on omnibase/admin's `CrudFieldsTestCase` |

## In memory

The entities that are not threads, and the services' static parts, need no kernel. Run in the
harness image with the omnisong checkouts on the autoloader:

```php
use Base\Music\Entity\Track;
use Base\Music\Entity\Work;
use Base\Music\Enum\Formation;

echo Track::formatDuration(247), ' ', Track::formatDuration(3765), "\n";

$work = (new Work())->setComposer('Reinhold Glière')->setTitle('Harp Concerto in E-flat major')->setOpus('op. 74')->setFormation(Formation::CONCERTO);
echo $work, ' | ', $work->getComposerSortKey(), "\n";

$track = (new Track())->setWork($work)->setMovement('I. Allegro moderato');
echo $track->getDisplayTitle(), "\n";
```

```
4:07 1:02:45
Reinhold Glière: Harp Concerto in E-flat major, op. 74 | glière reinhold glière
Reinhold Glière: Harp Concerto in E-flat major, op. 74 – I. Allegro moderato
```

`Release` and `Video` are threads, whose constructor needs the kernel: the suite builds them
with `ReflectionClass::newInstanceWithoutConstructor()` (and a subclass that keeps its texts
in plain properties where translations are read, `ImporterTest`'s `UntranslatedRelease`).
