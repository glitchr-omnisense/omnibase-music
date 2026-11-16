<?php

namespace Base\Music\Repository;

use Base\Database\Repository\Finder\Change;
use Base\Video\Entity\Video;
use Base\Database\Repository\Finder\Group;
use Base\Music\Entity\Track;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Track|null find($id, $lockMode = null, $lockVersion = null)
 * @method Track|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Track[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class TrackRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Track::class);
    }

    /** The tracks the player has nothing to play for: no excerpt of the site's, no preview. */
    public function countSilent(): int
    {
        return (int) $this->countBySampleNullAndPreviewUrlNull();
    }

    /** One more play of a track: added in the database itself, so two listeners at once are two plays. */
    public function countPlay(Track $track): void
    {
        // plays = plays + 1 in the database itself: two listeners at once are two plays.
        $this->updateBy(['id' => $track->getId()], ['plays' => Change::add(1)]);
    }

    /** The plays of every track together. */
    public function countPlays(): int
    {
        // The database's SUM(), null without a play counted: COALESCE(..., 0) was the 0.
        return (int) ($this->sumOfPlaysBy([]) ?? 0);
    }

    /** @return list<Track> those with an excerpt of the site's - all of them, or only the ones whose waveform is missing */
    public function findWithSample(bool $onlyWithoutPeaks = false): array
    {
        $criteria = [Group::by('SampleNotNullOrPreviewUrlNotNull')];
        if ($onlyWithoutPeaks) {
            $criteria[] = Group::by('PeaksNull');
        }

        return $this->findBy($criteria, ['id' => 'ASC']);
    }

    /** The track a film is of (the first, when several share it): its work, its performers, its release. */
    public function findOneByVideo(Video $video): ?Track
    {
        return $this->findOneBy(['videos' => $video], ['id' => 'ASC']);
    }
}
