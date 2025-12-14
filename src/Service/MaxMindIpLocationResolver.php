<?php

declare(strict_types=1);

namespace WillVincent\SessionManager\Service;

use GeoIp2\Model\City;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Throwable;
use WillVincent\SessionManager\Data\SessionLocation;
use WillVincent\SessionManager\Enums\LocationConfidence;

class MaxMindIpLocationResolver
{
    private ?Reader $reader = null;

    public function __construct(
        ?string $databasePath,
        private readonly CacheRepository $cache,
        private readonly int $cacheTtl,
        private readonly bool $storeCoordinates = false,
    ) {
        if ($databasePath && is_file($databasePath)) {
            $this->reader = new Reader($databasePath);
        }
    }

    public function resolve(string $ip): ?SessionLocation
    {
        if (!$this->reader instanceof Reader) {
            return null;
        }

        if (! config('session-manager.cache.enabled')) {
            return $this->lookup($ip);
        }

        $cacheKey = $this->cacheKey($ip);

        return $this->cache->remember(
            $cacheKey,
            $this->cacheTtl,
            fn (): ?SessionLocation => $this->lookup($ip),
        );
    }

    public function lookup(string $ip): ?SessionLocation
    {
        try {
            $record = $this->reader?->city($ip);
            if (!$record instanceof City) {
                return null;
            }

            $confidence = match (true) {
                $record->city->name && $record->mostSpecificSubdivision->name => LocationConfidence::HIGH,
                $record->city->name => LocationConfidence::MEDIUM,
                $record->country->isoCode => LocationConfidence::LOW,
                default => null,
            };

            return new SessionLocation(
                countryCode: $record->country->isoCode,
                region: $record->mostSpecificSubdivision->name,
                city: $record->city->name,
                timezone: $record->location->timeZone,
                latitude: $this->storeCoordinates ? $record->location->latitude : null,
                longitude: $this->storeCoordinates ? $record->location->longitude : null,
                confidence: $confidence,
                source: 'maxmind',
            );
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable) {
            // corrupt db, invalid ip, etc — fail closed
            return null;
        }
    }

    private function cacheKey(string $ip): string
    {
        $key = array_filter([
            config('session-manager.cache.key'),
            hash('sha256', $ip),
        ]);

        return implode(':', $key);
    }
}
