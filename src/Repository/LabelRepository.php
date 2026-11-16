<?php

namespace Base\Music\Repository;

use Base\Database\Repository\Finder\Group;
use Base\Music\Entity\Label;
use Base\Database\Repository\Finders;
use Base\Database\Repository\ServiceEntityRepository;

/**
 * @method Label|null find($id, $lockMode = null, $lockVersion = null)
 * @method Label|null findOneBy(array $criteria, ?array $orderBy = null)
 * @method Label[]    findBy(array $criteria, ?array $orderBy = null, $limit = null, $offset = null)
 */
class LabelRepository extends ServiceEntityRepository
{
    public function __construct(Finders $finders)
    {
        parent::__construct($finders, Label::class);
    }

    /** The label a catalogue names, whatever the case it writes it in ("Exemple records", "EXEMPLE RECORDS"). */
    public function findOneByName(string $name): ?Label
    {
        return $this->findOneBy([Group::by('InsensitiveName', mb_strtolower(trim($name)))]);
    }
}
