<?php

namespace Base\Music\Tests\Persistence;

use Base\Music\Entity\Track;
use Base\Music\Repository\TrackRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use Base\Testing\Engines;
use PHPUnit\Framework\TestCase;

/** TrackRepository's statements give the same rows on Doctrine and on Eloquent. */
final class TrackStatementsTest extends TestCase
{
    private static Engines $engines;

    /** @return array<string, array{0: string}> */
    public static function backends(): array
    {
        return Engines::backends();
    }

    public static function setUpBeforeClass(): void
    {
        self::$engines = Engines::of([Track::class]);
    }

    /** @dataProvider backends */
    #[DataProvider('backends')]
    public function testCountPlayAddsOnePlayInTheDatabase(string $backend): void
    {
        $id = self::$engines->insert('music_track', ['disc' => 1, 'plays' => 41, 'release_id' => 1]);
        $other = self::$engines->insert('music_track', ['disc' => 1, 'plays' => 7, 'release_id' => 1]);

        (new TrackRepository(self::$engines->finders($backend)))->countPlay(self::stub(Track::class, $id));

        self::assertSame(42, (int) self::scalar("SELECT plays FROM music_track WHERE id = $id"));
        self::assertSame(7, (int) self::scalar("SELECT plays FROM music_track WHERE id = $other"), 'the other rows untouched');
    }

    /** An entity of that id, never read: what a statement taking the entity is given. */
    private static function stub(string $class, int $id): object
    {
        $entity = (new \ReflectionClass($class))->newInstanceWithoutConstructor();
        (new \ReflectionProperty($class, 'id'))->setValue($entity, $id);

        return $entity;
    }

    private static function scalar(string $sql): mixed
    {
        return self::$engines->pdo()->query($sql)->fetchColumn();
    }
}
