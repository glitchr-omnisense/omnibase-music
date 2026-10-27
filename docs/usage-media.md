---
title: Waveforms and posters
order: 33
---

# Waveforms and posters

Both run ffmpeg on the server through symfony/process. Without it they compute nothing, say
so, and the pages keep working: a plain line instead of a waveform, the poster as it is.

## Waveforms

```bash
bin/console music:peaks              # the tracks with a file of the site's and no waveform yet
bin/console music:peaks --all        # every track with a file, again
bin/console music:peaks --track 12   # one track
```

or a record's **Waveforms** action in the back office (`/admin/releases/{entityId}/peaks`).
`-v` lists each track (`✓` computed, `-` skipped). Without ffmpeg the command warns and exits
0.

`Base\Music\Service\Peaks::compute(Track $track, int $count = 200): bool` sets a track's peaks
(nothing is flushed): of its own file, else of its preview when the preview is an `https://`
address ffmpeg reads itself. ffmpeg decodes it to raw mono PCM at 8 kHz
(`ffmpeg -v error -nostdin -i <file> -ac 1 -ar 8000 -f s16le -`, 300 s at most), and
`Peaks::reduce()` cuts the samples into 200 equal windows, takes each window's RMS and scales
them so the loudest is 1 (three decimals). Fewer samples than windows give one value a sample;
silence gives zeros. `isAvailable()` asks `ffmpeg -version` once. A new file forgets the old
waveform (`Track::setSample()`).

```php
use Base\Music\Service\Peaks;

// A second of a 440 Hz tone that fades out, as ffmpeg gives it: mono, 16 bits little-endian, 8 kHz.
$pcm = '';
for ($i = 0; $i < 8000; ++$i) {
    $pcm .= pack('v', (int) (sin(2 * M_PI * 440 * $i / 8000) * 20000 * (1 - $i / 8000)) & 0xFFFF);
}
echo implode(' ', Peaks::reduce($pcm, 8)), "\n";
```

```
1 0.867 0.734 0.601 0.468 0.335 0.203 0.077
```

## Posters

```bash
bin/console music:posters               # the films without a poster
bin/console music:posters --all         # every film, a poster set by hand included
bin/console music:posters --video 7     # one film
```

or a film's **Pick from the film** action (`/admin/videos/{entityId}/poster`).
`Base\Music\Service\PosterPicker::pick(Video $video): bool` chooses among the film's own
frames, with no machine learning:

- a YouTube film: its frames at a quarter, a half and three quarters (`maxres`, `sd` or `hq`,
  the largest there is), and the thumbnail its channel chose, weighted 0.8 (its lettering
  reads as sharpness);
- a Vimeo film: its thumbnail (oEmbed);
- a film of the site's: six frames read by ffmpeg at 10, 25, 40, 55, 70 and 85% of its length
  (120 s when it has none).

Each picture has its letterbox bars cut off (`uncropped()`), then is measured on a copy 96
pixels wide (`PosterPicker::score()`, 0 to 1): exposure (best around 48% luminance, 0.25),
contrast (0.20), sharpness by the Laplacian's mean (0.25), colourfulness (Hasler and
Süsstrunk, 0.10), and the share of skin or warm wood and gilding - the musician and the
instrument rather than an empty stage (0.20). A mostly black frame scores a quarter. The best
becomes the poster, a JPEG file of the site (86% quality, 1280 pixels wide at most), so a
visitor's page asks nothing of the platform before a click. Fetches time out after 8 seconds;
an answer other than 200 is skipped.
