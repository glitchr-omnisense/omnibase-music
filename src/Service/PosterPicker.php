<?php

namespace Base\Music\Service;

use Base\Database\Attribute\Uploader;
use Base\Music\Entity\Video;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Process\Process;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * A video's poster, chosen among its own frames with no artificial
 * intelligence: a few pictures of the film - YouTube's frames at a quarter,
 * a half and three quarters of it and the thumbnail its channel chose,
 * Vimeo's thumbnail, a file of the site's own read by ffmpeg at several
 * moments - each measured on a small copy, the black bars of a letterbox
 * cut off first: its exposure (neither dark nor burnt), its contrast, its
 * sharpness (a blurred or moving frame is soft), its colour, and how much
 * of it is skin or the warm wood and gilding of an instrument (the
 * musician, her harp, his violin) rather than an empty stage. The best one
 * becomes the poster, a file of the site: a visitor's page asks nothing of
 * the platform before a click.
 */
class PosterPicker
{
    /** Pictures measured this wide; enough for light, contrast and edges. */
    private const SAMPLE = 96;

    /** What a channel's designed thumbnail weighs against a frame of the film. */
    private const DESIGNED = 0.8;

    public function __construct(
        private readonly ?HttpClientInterface $http = null,
        private readonly ?LoggerInterface $logger = null,
        private readonly string $ffmpeg = 'ffmpeg',
    ) {
    }

    /**
     * Gives $video the best of its frames as its poster; false when no frame
     * could be had (no platform answer, no ffmpeg for a file of the site).
     */
    public function pick(Video $video): bool
    {
        $best = null;
        $bestScore = -1.0;
        foreach ($this->candidates($video) as $image => $weight) {
            $picture = @imagecreatefromstring($image);
            if (false === $picture) {
                continue;
            }
            $picture = self::uncropped($picture);
            $score = self::score($picture) * $weight;
            if ($score > $bestScore) {
                $best = $picture;
                $bestScore = $score;
            }
        }
        if (null === $best) {
            return false;
        }
        $width = imagesx($best);
        if ($width > 1280) {
            $best = imagescale($best, 1280) ?: $best;
        }
        $path = sys_get_temp_dir().'/poster-'.bin2hex(random_bytes(6)).'.jpg';
        imagejpeg($best, $path, 86);
        $video->setPoster(new File($path));

        return true;
    }

    /**
     * How good a picture is as a poster, 0..1: exposure, contrast,
     * sharpness, colour, and the share of skin and warm wood.
     */
    public static function score(\GdImage $picture): float
    {
        $width = self::SAMPLE;
        $height = max(1, (int) round(imagesy($picture) * $width / max(1, imagesx($picture))));
        $small = imagecreatetruecolor($width, $height);
        imagecopyresampled($small, $picture, 0, 0, 0, 0, $width, $height, imagesx($picture), imagesy($picture));

        $luma = [];
        $sum = $sumSq = $dark = $warm = 0.0;
        $rg = $yb = [];
        for ($y = 0; $y < $height; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $l = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
                $luma[$y][$x] = $l;
                $sum += $l;
                $sumSq += $l * $l;
                $dark += $l < 0.08 ? 1 : 0;
                $rg[] = $r - $g;
                $yb[] = 0.5 * ($r + $g) - $b;
                $warm += self::isWarm($r, $g, $b) ? 1 : 0;
            }
        }
        $n = $width * $height;
        $mean = $sum / $n;
        $deviation = sqrt(max(0.0, $sumSq / $n - $mean * $mean));

        // Edges: the mean of the Laplacian's size; a soft frame has few.
        $edges = 0.0;
        for ($y = 1; $y < $height - 1; ++$y) {
            for ($x = 1; $x < $width - 1; ++$x) {
                $edges += abs(4 * $luma[$y][$x] - $luma[$y - 1][$x] - $luma[$y + 1][$x] - $luma[$y][$x - 1] - $luma[$y][$x + 1]);
            }
        }
        $edges /= max(1, ($width - 2) * ($height - 2));

        // Hasler and Süsstrunk's colourfulness, on 0..255 values.
        $colour = self::spread($rg, $yb);

        $exposure = max(0.0, 1 - abs($mean - 0.48) * 2.2);
        $score = 0.25 * $exposure
            + 0.20 * min(1.0, $deviation / 0.24)
            + 0.25 * min(1.0, $edges / 0.09)
            + 0.10 * min(1.0, $colour / 80)
            + 0.20 * min(1.0, ($warm / $n) / 0.28);
        // Mostly black (a fade, a title on black): hardly a poster.
        if ($dark / $n > 0.55 || $mean < 0.1) {
            $score *= 0.25;
        }

        return round($score, 4);
    }

    /** Skin (in YCbCr's usual box) or the warm brown and gold of wood and gilding. */
    private static function isWarm(int $r, int $g, int $b): bool
    {
        $cb = 128 - 0.168736 * $r - 0.331264 * $g + 0.5 * $b;
        $cr = 128 + 0.5 * $r - 0.418688 * $g - 0.081312 * $b;
        if ($cb >= 77 && $cb <= 127 && $cr >= 137 && $cr <= 173 && $r > 60) {
            return true;
        }
        $max = max($r, $g, $b);
        $min = min($r, $g, $b);
        if ($max < 60 || $max === $min || $max !== $r) {
            return false;
        }
        $hue = 60 * (($g - $b) / ($max - $min));

        return $hue >= 18 && $hue <= 50 && ($max - $min) / $max >= 0.35;
    }

    /** @param list<float> $rg @param list<float> $yb */
    private static function spread(array $rg, array $yb): float
    {
        $stats = static function (array $values): array {
            $mean = array_sum($values) / max(1, \count($values));
            $variance = 0.0;
            foreach ($values as $value) {
                $variance += ($value - $mean) ** 2;
            }

            return [$mean, sqrt($variance / max(1, \count($values)))];
        };
        [$mrg, $srg] = $stats($rg);
        [$myb, $syb] = $stats($yb);

        return sqrt($srg ** 2 + $syb ** 2) + 0.3 * sqrt($mrg ** 2 + $myb ** 2);
    }

    /** The picture without the black bars of a letterbox, above and below or on its sides. */
    public static function uncropped(\GdImage $picture): \GdImage
    {
        $width = imagesx($picture);
        $height = imagesy($picture);
        $blackRow = static function (int $y) use ($picture, $width): bool {
            for ($x = 0; $x < $width; $x += max(1, intdiv($width, 32))) {
                $rgb = imagecolorat($picture, $x, $y);
                if (((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) > 60) {
                    return false;
                }
            }

            return true;
        };
        $blackColumn = static function (int $x) use ($picture, $height): bool {
            for ($y = 0; $y < $height; $y += max(1, intdiv($height, 32))) {
                $rgb = imagecolorat($picture, $x, $y);
                if (((($rgb >> 16) & 0xFF) + (($rgb >> 8) & 0xFF) + ($rgb & 0xFF)) > 60) {
                    return false;
                }
            }

            return true;
        };
        $top = 0;
        while ($top < $height / 3 && $blackRow($top)) {
            ++$top;
        }
        $bottom = $height - 1;
        while ($bottom > $height * 2 / 3 && $blackRow($bottom)) {
            --$bottom;
        }
        $left = 0;
        while ($left < $width / 3 && $blackColumn($left)) {
            ++$left;
        }
        $right = $width - 1;
        while ($right > $width * 2 / 3 && $blackColumn($right)) {
            --$right;
        }
        if (0 === $top && $height - 1 === $bottom && 0 === $left && $width - 1 === $right) {
            return $picture;
        }

        return imagecrop($picture, ['x' => $left, 'y' => $top, 'width' => $right - $left + 1, 'height' => $bottom - $top + 1]) ?: $picture;
    }

    /**
     * The frames to choose from: image bytes => weight. The thumbnail a channel designed weighs less than the
     * film's own frames: its lettering ("LIVESTREAM", a title in capitals) reads as sharpness, and a frame of
     * the musician playing makes the better poster when the two are close.
     *
     * @return iterable<string, float>
     */
    private function candidates(Video $video): iterable
    {
        if ($video->isLocal()) {
            yield from $this->frames($video);

            return;
        }
        if (null !== $id = $video->getYoutubeId()) {
            // A frame at a quarter, a half and three quarters of the film, at the largest size it has; then the
            // thumbnail the channel chose.
            foreach ([1, 2, 3] as $moment) {
                foreach (['maxres', 'sd', 'hq'] as $size) {
                    if (null !== $image = $this->fetch(sprintf('https://i.ytimg.com/vi/%s/%s%d.jpg', rawurlencode($id), $size, $moment))) {
                        yield $image => 1.0;
                        break;
                    }
                }
            }
            foreach (['maxresdefault', 'hqdefault'] as $chosen) {
                if (null !== $image = $this->fetch(sprintf('https://i.ytimg.com/vi/%s/%s.jpg', rawurlencode($id), $chosen))) {
                    yield $image => self::DESIGNED;
                    break;
                }
            }

            return;
        }
        if (null !== $id = $video->getVimeoId()) {
            $answer = $this->fetch('https://vimeo.com/api/oembed.json?width=1280&url='.rawurlencode('https://vimeo.com/'.$id));
            $thumbnail = null !== $answer ? (json_decode($answer, true)['thumbnail_url'] ?? null) : null;
            if (\is_string($thumbnail) && null !== $image = $this->fetch($thumbnail)) {
                yield $image => 1.0;
            }
        }
    }

    /** @return iterable<string, float> frames of the site's own file, at a tenth, a quarter, ... of it */
    private function frames(Video $video): iterable
    {
        $file = Uploader::get($video, 'file');
        if (null === $file || !is_file($file->getPathname())) {
            return;
        }
        $length = $video->getDuration() ?: 120;
        foreach ([0.1, 0.25, 0.4, 0.55, 0.7, 0.85] as $share) {
            $process = new Process([$this->ffmpeg, '-v', 'error', '-ss', (string) round($length * $share, 1), '-i', $file->getPathname(), '-frames:v', '1', '-f', 'image2pipe', '-vcodec', 'mjpeg', '-']);
            $process->setTimeout(30);
            try {
                $process->run();
            } catch (\Throwable $e) {
                $this->logger?->warning('music: ffmpeg could not read a frame of {video}: {error}', ['video' => $video->getId(), 'error' => $e->getMessage()]);

                return;
            }
            if ($process->isSuccessful() && '' !== $process->getOutput()) {
                yield $process->getOutput() => 1.0;
            }
        }
    }

    private function fetch(string $url): ?string
    {
        if (null === $this->http) {
            return null;
        }
        try {
            $response = $this->http->request('GET', $url, ['timeout' => 8]);

            return 200 === $response->getStatusCode() ? $response->getContent() : null;
        } catch (\Throwable $e) {
            $this->logger?->info('music: {url} did not answer: {error}', ['url' => $url, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
