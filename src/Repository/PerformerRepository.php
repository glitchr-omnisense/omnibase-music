<?php

namespace Base\Music\Repository;

use Base\Music\Entity\Performer;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Performer|null find($id, $lockMode = null, $lockVersion = null)
 * @method Performer|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Performer[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class PerformerRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Performer::class);
    }
}
