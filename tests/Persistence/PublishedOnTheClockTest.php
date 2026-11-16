<?php

namespace Base\Music\Tests\Persistence;

use Base\Contract\Locales;
use Base\Contract\Runtime;
use Base\Entity\Thread;
use Base\Entity\ThreadIntl;
use Base\Enum\ThreadState;
use Base\Enum\WorkflowState;
use Base\Music\Entity\Label;
use Base\Music\Entity\Release;
use Base\Music\Entity\Track;
use Base\Music\Entity\Work;
use Base\Music\Repository\ReleaseRepository;
use Base\Music\Repository\TrackRepository;
use Base\Video\Entity\Video;
use Base\Testing\Engines;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Symfony\Component\Clock\MockClock;

/**
 * Published, out and upcoming are read on the application's clock, in UTC (Runtime::clock()), the same rows on
 * Doctrine and on Eloquent: no query reads the database's clock.
 */
final class PublishedOnTheClockTest extends TestCase
{
    private static Engines $engines;

    /** @var array<string, int> */
    private static array $ids = [];

    private MockClock $clock;

    private string $zone;

    /** The Runtime's Locales before the case (a host's, booted earlier), put back after it. */
    private ?Locales $locales = null;

    /** @var Locales&object{zone: string} the visitor the cases are */
    private Locales $visitor;

    /** The visitor's zone (the Runtime's Locales') - and PHP's, as a host puts it. */
    private function visitorIn(string $zone): void
    {
        $this->visitor->zone = $zone;
        date_default_timezone_set($zone);
    }

    public static function setUpBeforeClass(): void
    {
        self::$engines = Engines::of([Thread::class, Release::class, Track::class, Video::class, Label::class, Work::class]);
        self::$ids = [
            // Published at 10:00 UTC on 2026-03-01, released that day.
            'at-ten' => self::release('at-ten', '2026-03-01 10:00:00', '2026-03-01'),
            // Published long ago, released the next day: upcoming until then.
            'next-day' => self::release('next-day', '2026-01-01 10:00:00', '2026-03-02'),
            'draft' => self::release('draft', '2026-01-01 10:00:00', '2026-01-01', ThreadState::DRAFT),
        ];
        // The films are omnibase/video's, a release's are its tracks': one track with two films, one with none.
        $track = self::track(self::$ids['at-ten'], 1);
        self::track(self::$ids['at-ten'], 2);
        self::$ids['film-concert'] = self::film('film-concert', $track);
        self::$ids['film-clip'] = self::film('film-clip', $track);
        self::$ids['track'] = $track;
    }

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return Engines::backends();
    }

    protected function setUp(): void
    {
        $this->zone = date_default_timezone_get();
        // The visitor's zone is the Runtime's Locales' (a host's, when one was booted before): a double, whose zone
        // each case chooses with visitorIn().
        $this->locales = Runtime::find(Locales::class);
        $this->visitor = new class($this->locales) implements Locales {
            public string $zone = 'UTC';
            public function __construct(private readonly ?Locales $host) {}
            public function getLocale(): string { return $this->host?->getLocale() ?? 'fr'; }
            public function getDefaultLocale(): string { return $this->host?->getDefaultLocale() ?? 'fr'; }
            public function getAvailableLocales(): array { return $this->host?->getAvailableLocales() ?? ['fr']; }
            public function getTimezone(): string { return $this->zone; }
        };
        Runtime::set(Locales::class, $this->visitor);
        $this->visitorIn('Asia/Tokyo');
        // PHP in Tokyo: 09:30 UTC is 18:30 there - a comparison of wall clocks would publish what is not yet.
        Runtime::set(ClockInterface::class, $this->clock = new MockClock('2026-03-01 09:30:00', 'UTC'));
    }

    protected function tearDown(): void
    {
        Runtime::set(ClockInterface::class, null);
        Runtime::set(Locales::class, $this->locales);
        date_default_timezone_set($this->zone);
    }

    private static function thread(string $class, string $slug, string $publishedAt, string $state): int
    {
        $id = self::$engines->insert('thread', ['uuid' => uniqid('', true), 'slug' => $slug, 'state' => $state, 'workflow' => WorkflowState::APPROVED,
            'createdAt' => '2026-01-01 10:00:00', 'updatedAt' => '2026-01-01 10:00:00', 'publishedAt' => $publishedAt, 'class' => self::$engines->discriminator($class)]);
        self::$engines->insert('threadIntl', ['title' => $slug, 'keywords' => '[]', 'locale' => 'fr', 'translatable_id' => $id, 'class' => self::$engines->discriminator(ThreadIntl::class)]);

        return $id;
    }

    private static function release(string $slug, string $publishedAt, string $releasedAt, string $state = ThreadState::PUBLISH): int
    {
        $id = self::thread(Release::class, $slug, $publishedAt, $state);
        self::$engines->insert('music_release', ['id' => $id, 'type' => 'album', 'releasedAt' => $releasedAt, 'upcoming' => 0, 'featured' => 0]);

        return $id;
    }

    private static function track(int $release, int $position): int
    {
        return self::$engines->insert('music_track', ['release_id' => $release, 'position' => $position, 'disc' => 1, 'plays' => 0, 'whole' => 0]);
    }

    private static function film(string $slug, int $track): int
    {
        $id = self::thread(Video::class, $slug, '2026-01-01 10:00:00', ThreadState::PUBLISH);
        self::$engines->insert('video', ['id' => $id, 'processing' => Video::PROCESSING_READY, 'views' => 0]);
        self::$engines->insert('music_track_video', ['music_track_id' => $track, 'video_id' => $id]);

        return $id;
    }

    /** @param iterable<object> $records @return list<string> */
    private static function slugs(iterable $records): array
    {
        return array_values(array_map(static fn (object $record): string => (string) $record->getSlug(), [...$records]));
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testReleasesOnTheApplicationsClock(string $backend): void
    {
        $releases = new ReleaseRepository(self::$engines->finders($backend));

        self::assertSame(['next-day'], self::slugs($releases->findPublished()), 'at-ten half an hour early, the draft never');
        self::assertSame(['next-day'], self::slugs($releases->findUpcoming()), 'released tomorrow (the visitor\'s day, in Tokyo)');
        self::assertSame([], self::slugs($releases->findLatest()));

        $this->clock->modify('2026-03-01 10:30:00 UTC');
        self::assertSame(['next-day', 'at-ten'], self::slugs($releases->findPublished()), 'half an hour after');
        self::assertSame(['at-ten'], self::slugs($releases->findLatest()), 'out today');
        self::assertSame('at-ten', $releases->findOnePublished('at-ten')?->getSlug());

        $this->clock->modify('2026-03-02 00:30:00 UTC');
        self::assertSame(['next-day', 'at-ten'], self::slugs($releases->findLatest()), 'the next day: out');
        self::assertSame([], self::slugs($releases->findUpcoming()));
    }

    /**
     * A release date (a date, no time) is compared with the VISITOR's day, in the visitor's zone: at 16:00 UTC on
     * 2026-03-01 it is already the 2nd in Tokyo (next-day is out) and still the 1st for a visitor in UTC (announced).
     * @dataProvider backends
     */
    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testAReleaseDateIsComparedWithTheVisitorsDay(string $backend): void
    {
        $releases = new ReleaseRepository(self::$engines->finders($backend));
        $this->clock->modify('2026-03-01 16:00:00 UTC');

        self::assertSame(['next-day', 'at-ten'], self::slugs($releases->findLatest()), 'in Tokyo: 01:00 on the 2nd');
        self::assertSame([], self::slugs($releases->findUpcoming()));

        $this->visitorIn('UTC');
        self::assertSame(['at-ten'], self::slugs($releases->findLatest()), 'in UTC: 16:00 on the 1st');
        self::assertSame(['next-day'], self::slugs($releases->findUpcoming()));
    }

    /**
     * The finder's own date word (IsOver) reads the application's clock too, in UTC, on both engines.
     *
     * @dataProvider backends
     */
    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testTheFindersIsOverReadsTheApplicationsClock(string $backend): void
    {
        $releases = new ReleaseRepository(self::$engines->finders($backend));
        $over = ['state' => ThreadState::PUBLISH, \Base\Database\Repository\Finder\Group::by('PublishedAtIsOver'), 'slug' => 'at-ten'];

        self::assertSame([], self::slugs($releases->findBy($over)), 'half an hour early');
        $this->clock->modify('2026-03-01 10:30:00 UTC');
        self::assertSame(['at-ten'], self::slugs($releases->findBy($over)), 'half an hour after');
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testTheFilmsAreThoseOfThePublishedReleasesTracks(string $backend): void
    {
        $releases = new ReleaseRepository(self::$engines->finders($backend));

        self::assertSame([], self::slugs($releases->findVideos()), 'their release half an hour early');

        $this->clock->modify('2026-03-01 10:30:00 UTC');
        self::assertSame(['film-concert', 'film-clip'], self::slugs($releases->findVideos()), 'its track\'s two films, in order');
        self::assertSame(['film-concert'], self::slugs($releases->findVideos(1)));
        $release = $releases->findOnePublished('at-ten');
        self::assertSame(['film-concert', 'film-clip'], self::slugs($release?->getVideos() ?? []), 'the release\'s films are its tracks\'');
        self::assertSame([2, 0], array_map(static fn (Track $track): int => \count($track->getVideos()), $release?->getTracks()->toArray() ?? []), 'a track without films');

        $video = $releases->findVideos(1)[0];
        self::assertSame(self::$ids['track'], (new TrackRepository(self::$engines->finders($backend)))->findOneByVideo($video)?->getId(), 'the track a film is of');
    }
}
