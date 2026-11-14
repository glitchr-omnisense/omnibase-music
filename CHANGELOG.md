# Changelog

## [Unreleased]

## [1.0.1] — 2026-11-14

### Changed
- The current names of what moved to omnibridge/symfony, in place of the former ones the core keeps as aliases only
  until 6.0: `Base\BaseBundle` → `Omnibridge\Symfony\Omnibase\BaseBundle`;
  `Base\Bundle\AbstractBaseBundle` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseBundle`;
  `Base\Bundle\AbstractBaseConfiguration` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseConfiguration`;
  `Base\Bundle\AbstractBaseExtension` → `Omnibridge\Symfony\Omnibase\Bundle\AbstractBaseExtension`.
  No behaviour change; the bundle no longer needs any alias of the core's `former-names*.php`.

## [1.0.0] — 2026-11-13

### Changed
- First stable release: installs with the default minimum-stability. Requires omnisense/omnibase `^5.1` (no longer
  the 3.0, 4.0 or 5.0 development branches), omnibridge/symfony `^1.1`, omnibase/admin `^1.0`, omnisense/omnisong `^1.0`, without `@dev`; `minimum-stability: dev` removed;
  branch alias `dev-main` = `1.x-dev`.
- CI: glitchr-studio/ci's package workflow (validate, PHPUnit, PHPStan level 5 with a baseline, a consumer install)
  and release workflow on a `v*` tag.
- README: the install line, how to enable the bundle under Symfony, a first use.
- `Release` and `Video::__toLink()` link through `Base\Contract\Link::to()` (the Urls contract; the same address under
  Symfony, in the core 3.x and 4.x) instead of BaseTrait's `getRouter()->generate()`, which the
  core deprecates since BaseTrait names contracts only (omnisense/omnibase 4.x, step F2f).

### Fixed
- Requires doctrine/doctrine-bundle and symfony/doctrine-bridge, whose classes `src/` uses: they came only with a host
  application (omnibridge/symfony suggests them), so the bundle installed alone failed to load its repositories.
- Neutral example data in docs and tests.
- README: Escape, with the focus in the bar, folds the sheet then tucks the bar into its handle
  and the music goes on, as `player.js` does; only the cross stops the music and hides the bar.
  The README said Escape did what the cross does.
