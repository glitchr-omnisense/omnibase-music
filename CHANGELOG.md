# Changelog

## 2.0.0

omnibase/music is a musician's site on omnisense/omnibase: the records and their label, the repertoire, the films, the
instrument, the people who play along, and one player for the whole page. It names only `Base\` classes: its
repositories extend `Base\Database\Repository\ServiceEntityRepository` and speak the core's finder language, its
services write through `Base\Contract\UnitOfWork` and read other classes through
`Base\Database\Repository\Repositories`. Each query is written once in the finder language, a track's plays counted in
the database itself (`updateBy()` with `Change::add()`) and summed by the finder (`sumOfPlaysBy()`), and compiled by the
engine of the persistence chosen (omnibridge/doctrine or omnibridge/eloquent).
