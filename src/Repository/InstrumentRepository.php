<?php

namespace Base\Music\Repository;

use Base\Music\Entity\Instrument;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Instrument|null find($id, $lockMode = null, $lockVersion = null)
 * @method Instrument|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Instrument[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class InstrumentRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Instrument::class);
    }

    /** @return list<Instrument> the visible ones, the featured first */
    public function findVisible(): array
    {
        return $this->findBy(['visible' => true], ['featured' => 'DESC', 'year' => 'ASC', 'id' => 'ASC']);
    }

    /** The featured instrument, or the first visible one. */
    public function findFeatured(): ?Instrument
    {
        return $this->findVisible()[0] ?? null;
    }
}
