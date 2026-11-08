# Changelog

## [Unreleased]

### Changed
- Accepts omnisense/omnibase 5.0 (`3.0.*-dev || 4.0.*-dev || 5.0.*-dev`; plan "omnibase agnostic", step S): the bundle runs
  unchanged on it, `Base\Bundle\AbstractBaseBundle` and the other former names it uses being aliases of
  omnibridge/symfony's classes there. The sites on 4.x resolve as before.
- `Release` and `Video::__toLink()` link through `Base\Contract\Link::to()` (the Urls contract; the same address under
  Symfony, in the core 3.x and 4.x) instead of BaseTrait's `getRouter()->generate()`, which the
  core deprecates since BaseTrait names contracts only (omnisense/omnibase 4.x, step F2f).

### Fixed
- README: Escape, with the focus in the bar, folds the sheet then tucks the bar into its handle
  and the music goes on, as `player.js` does; only the cross stops the music and hides the bar.
  The README said Escape did what the cross does.
