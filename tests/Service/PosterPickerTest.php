<?php

namespace Base\Music\Tests\Service;

use Base\Music\Service\PosterPicker;
use PHPUnit\Framework\TestCase;

/**
 * How a frame is measured as a poster, with no AI: a black frame (a fade) and a flat grey one lose to a
 * sharp, lit picture with skin and warm wood in it; a letterbox's black bars are cut off first.
 */
final class PosterPickerTest extends TestCase
{
    /** A 320x180 picture: a light background, a warm (skin, wood) shape and fine stripes - a lit, sharp frame. */
    private static function lit(): \GdImage
    {
        $picture = imagecreatetruecolor(320, 180);
        imagefill($picture, 0, 0, imagecolorallocate($picture, 205, 210, 215));
        imagefilledellipse($picture, 160, 90, 120, 150, imagecolorallocate($picture, 224, 172, 140));
        for ($x = 20; $x < 100; $x += 6) {
            imageline($picture, $x, 20, $x, 160, imagecolorallocate($picture, 150, 100, 40));
        }

        return $picture;
    }

    private static function flat(int $grey): \GdImage
    {
        $picture = imagecreatetruecolor(320, 180);
        imagefill($picture, 0, 0, imagecolorallocate($picture, $grey, $grey, $grey));

        return $picture;
    }

    public function testALitSharpWarmFrameWinsOverABlackOrAFlatOne(): void
    {
        $lit = PosterPicker::score(self::lit());
        self::assertGreaterThan(PosterPicker::score(self::flat(4)), $lit, 'over a black frame');
        self::assertGreaterThan(PosterPicker::score(self::flat(128)), $lit, 'over a flat grey one');
        self::assertLessThan(0.1, PosterPicker::score(self::flat(4)), 'a black frame is hardly a poster');
    }

    public function testALetterboxsBlackBarsAreCutOff(): void
    {
        $boxed = imagecreatetruecolor(320, 240);
        imagefill($boxed, 0, 0, imagecolorallocate($boxed, 0, 0, 0));
        imagecopy($boxed, self::lit(), 0, 30, 0, 0, 320, 180);
        $cut = PosterPicker::uncropped($boxed);
        self::assertSame([320, 180], [imagesx($cut), imagesy($cut)]);
    }
}
