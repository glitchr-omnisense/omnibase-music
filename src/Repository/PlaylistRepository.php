<?php

namespace Base\Music\Repository;

use Base\Music\Entity\Playlist;
use Base\Music\Enum\PlaylistKind;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Playlist|null find($id, $lockMode = null, $lockVersion = null)
 * @method Playlist|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Playlist[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class PlaylistRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Playlist::class);
    }

    /** @return list<Playlist> the visible ones (of one kind), the featured first, then in their order */
    public function findVisible(?PlaylistKind $kind = null): array
    {
        return $this->findBy(['visible' => true] + ($kind ? ['kind' => $kind] : []), ['featured' => 'DESC', 'position' => 'ASC', 'id' => 'ASC']);
    }

    /** The featured playlist (of one kind), or the first visible one. */
    public function findFeatured(?PlaylistKind $kind = null): ?Playlist
    {
        return $this->findVisible($kind)[0] ?? null;
    }
}
