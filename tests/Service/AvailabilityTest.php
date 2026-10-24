<?php

namespace Base\Music\Tests\Service;

use Base\Music\Entity\Release;
use Base\Music\Entity\Track;
use Base\Music\Service\Availability;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Whether a track can be heard: an album announced has its singles out
 * (their own day behind us) and its other tracks waiting for its day; a
 * track withheld in a country is greyed there, and only there.
 */
final class AvailabilityTest extends TestCase
{
    private static function availability(array $headers = [], array $cookies = []): Availability
    {
        $request = new Request([], [], [], $cookies);
        foreach ($headers as $name => $value) {
            $request->headers->set($name, $value);
        }
        $requests = new RequestStack();
        $requests->push($request);

        return new Availability($requests);
    }

    /** A release without the Thread's constructor (it needs omnibase's kernel), as ReleaseTest makes one. */
    private static function release(): Release
    {
        return (new \ReflectionClass(Release::class))->newInstanceWithoutConstructor();
    }

    private static function upcomingAlbum(): Release
    {
        return self::release()->setUpcoming(true)->setReleasedAt(new \DateTimeImmutable('+30 days'));
    }

    public function testAnAnnouncedAlbumPlaysItsSinglesAndKeepsItsOtherTracksForItsDay(): void
    {
        $album = self::upcomingAlbum();
        $single = (new Track())->setRelease($album)->setAvailableFrom(new \DateTimeImmutable('-3 days'));
        $later = (new Track())->setRelease($album);
        $availability = self::availability();

        self::assertNull($availability->why($single), 'the single, out three days ago, plays');
        $why = $availability->why($later);
        self::assertSame(Availability::LATER, $why['reason'] ?? null, 'the other track waits');
        self::assertSame($album->getReleasedAt()->format('Y-m-d'), $why['date']->format('Y-m-d'), 'for the album\'s day');
    }

    public function testATrackOfARecordOutPlaysUnlessItsOwnDayIsAhead(): void
    {
        $out = self::release()->setReleasedAt(new \DateTimeImmutable('-1 year'));
        self::assertNull(self::availability()->why((new Track())->setRelease($out)));
        $ahead = (new Track())->setRelease($out)->setAvailableFrom(new \DateTimeImmutable('+2 days'));
        self::assertSame(Availability::LATER, self::availability()->why($ahead)['reason'] ?? null);
    }

    public function testATrackWithheldInACountryIsGreyedThereAndOnlyThere(): void
    {
        $track = (new Track())->setRelease(self::release()->setReleasedAt(new \DateTimeImmutable('-1 year')))->setBlockedIn(['jp', 'US', 'nonsense', 'US']);
        self::assertSame(['JP', 'US'], $track->getBlockedIn(), 'codes kept upper-case, once, the rest dropped');

        self::assertSame(Availability::REGION, self::availability(['CF-IPCountry' => 'JP'])->why($track)['reason'] ?? null, 'the CDN\'s country');
        self::assertSame(Availability::REGION, self::availability([], ['country' => 'us'])->why($track)['reason'] ?? null, 'the site\'s cookie');
        self::assertSame(Availability::REGION, self::availability(['Accept-Language' => 'en-US,en;q=0.8'])->why($track)['reason'] ?? null, 'the language\'s region, at the last');
        self::assertNull(self::availability(['CF-IPCountry' => 'FR', 'Accept-Language' => 'en-US'])->why($track), 'the header before the language');
        self::assertNull(self::availability(['CF-IPCountry' => 'XX', 'Accept-Language' => 'fr'])->why($track), 'nowhere known: nothing withheld');
    }
}
