<?php

namespace Base\Music\Repository;

use Base\Video\Entity\Video;
use Base\Contract\Runtime;
use Base\Database\Repository\Finder\Group;
use Base\Enum\ThreadState;
use Base\Music\Entity\Release;
use Base\Music\Enum\ReleaseType;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;
use Base\Database\Type\Utc;

/**
 * @method Release|null find($id, $lockMode = null, $lockVersion = null)
 * @method Release|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Release[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Release::class);
    }

    /**
     * The discography: every published release, the newest first - the
     * announced ones included, they say so themselves.
     *
     * @return list<Release>
     */
    public function findPublished(): array
    {
        return $this->findBy(self::published(), self::NEWEST_FIRST);
    }

    /**
     * The last ones out (the announced ones left aside); of one type when
     * asked - a home page shows the albums, not the singles between them.
     *
     * @return list<Release>
     */
    public function findLatest(int $limit = 3, ?ReleaseType $type = null): array
    {
        return $this->findBy(self::out() + (null !== $type ? ['type' => $type] : []), self::NEWEST_FIRST, $limit);
    }

    /** The featured release, or the newest one out. */
    public function findFeatured(): ?Release
    {
        return $this->findOneBy(self::out(), ['featured' => 'DESC', 'releasedAt' => 'DESC', 'id' => 'DESC']);
    }

    public function findOnePublished(string $slug): ?Release
    {
        return $this->findOneBy(self::published() + ['slug' => $slug]);
    }

    /** @return list<Release> announced or dated in the future, the nearest first */
    public function findUpcoming(): array
    {
        return $this->findBy([...self::published(), Group::by('UpcomingTrueOrReleasedAtGreaterThan', self::today())], ['releasedAt' => 'ASC', 'id' => 'ASC']);
    }

    /**
     * The visitor's today on the application's clock: the day a release date (a date, no time) is compared with - in
     * the visitor's zone (PHP's, which the host sets from the timezone cookie).
     */
    private static function today(): \DateTimeImmutable
    {
        return Runtime::clock()->now()->setTimezone(new \DateTimeZone(Runtime::find(\Base\Contract\Locales::class)?->getTimezone() ?? date_default_timezone_get()))->setTime(0, 0);
    }

    /**
     * The site's films: those of the published releases' tracks (omnibase/video's), the newest release first and each
     * in its tracks' order, each film once, those its own platform still shows (Video::isReachable()). A musician's
     * releases are few: they are read, their films gathered here.
     *
     * @return list<Video>
     */
    public function findVideos(?int $limit = null): array
    {
        $videos = [];
        foreach ($this->findPublished() as $release) {
            foreach ($release->getVideos() as $video) {
                if ($video->isReachable()) {
                    $videos[spl_object_id($video)] = $video;
                }
            }
        }

        return \array_slice(array_values($videos), 0, $limit);
    }

    private const NEWEST_FIRST = ['releasedAt' => 'DESC', 'publishedAt' => 'DESC', 'id' => 'DESC'];

    /** @return array<int|string, mixed> out already: published, not announced, released today at the latest */
    private static function out(): array
    {
        return [...self::published(), 'upcoming' => false, Group::by('ReleasedAtNullOrReleasedAtLowerEqualTo', self::today())];
    }

    /**
     * Published, and not scheduled for later: no publication date, or one at or before the application's clock,
     * in UTC (one time for every engine: no database clock).
     *
     * @return array<int|string, mixed>
     */
    private static function published(): array
    {
        return ['state' => ThreadState::PUBLISH, Group::by('PublishedAtNullOrPublishedAtLowerEqualTo', Utc::from(Runtime::clock()->now()))];
    }
}
