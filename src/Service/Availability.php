<?php

namespace Base\Music\Service;

use Base\Music\Entity\Track;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Whether a track can be heard here and now: not before its day (a track
 * kept for its album's release, while a single of it is out already), and
 * not in a country its rights stop at (Track::$blockedIn, set by hand in the
 * back office).
 *
 * The visitor's country is the one the site is told: a `country` cookie, the
 * CDN's or the proxy's header (CF-IPCountry, X-Country), as the marketplace's
 * age gate reads it; else the region of the browser's first language
 * ("de-CH": CH), a guess a visitor abroad may not fit. None known: nothing is
 * withheld for a country.
 */
class Availability
{
    public const LATER = 'later';
    public const REGION = 'region';

    /** The request the country was read from: each request its own, the process may serve several. */
    private ?Request $readFrom = null;
    private ?string $country = null;

    public function __construct(private readonly ?RequestStack $requests = null)
    {
    }

    /**
     * Why $track cannot be heard, null when it can: ['reason' => 'later',
     * 'date' => its day or null] or ['reason' => 'region'].
     *
     * @return array{reason: string, date?: ?\DateTimeInterface}|null
     */
    public function why(Track $track): ?array
    {
        if (!$track->isOutOn(new \DateTimeImmutable('today'))) {
            return ['reason' => self::LATER, 'date' => $track->getOutDate()];
        }
        if ($track->isBlockedIn($this->country())) {
            return ['reason' => self::REGION];
        }

        return null;
    }

    public function allows(Track $track): bool
    {
        return null === $this->why($track);
    }

    /** The visitor's country (ISO 3166, "FR"), null when nothing says it. */
    public function country(): ?string
    {
        $request = $this->requests?->getMainRequest();
        if ($request !== $this->readFrom || null === $request) {
            $this->readFrom = $request;
            $this->country = self::countryOf($request);
        }

        return $this->country;
    }

    public static function countryOf(?Request $request): ?string
    {
        if (null === $request) {
            return null;
        }
        foreach ([$request->cookies->get('country'), $request->headers->get('CF-IPCountry'), $request->headers->get('X-Country')] as $said) {
            // Cloudflare says XX for an unknown place, T1 for Tor.
            if (\is_string($said) && preg_match('/^[A-Za-z]{2}$/', $said) && !\in_array(strtoupper($said), ['XX', 'T1'], true)) {
                return strtoupper($said);
            }
        }
        $first = $request->getLanguages()[0] ?? null;
        if (\is_string($first) && preg_match('/^[a-z]{2,3}_([A-Z]{2})$/', $first, $match)) {
            return $match[1];
        }

        return null;
    }
}
