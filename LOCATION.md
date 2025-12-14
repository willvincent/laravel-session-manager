# IP-Based Session Location Enrichment

This package can optionally enrich user sessions with **approximate geographic location data** based on the session IP address.

Location enrichment is **entirely optional** and disabled by default. If not configured, all session features continue to work normally.

---

## How Location Enrichment Works

* Session IP addresses are resolved to approximate locations using **MaxMind GeoLite2**
* Lookups are performed **locally** using a downloaded database (no runtime API calls)
* Results are **cached by IP address** using Laravel’s cache system
* Location data is considered **approximate** and should be treated as informational only

---

## Requirements

To enable location enrichment, you must install the optional dependency:

```bash
composer require geoip2/geoip2
```

For best localization support (country and timezone names), you may also install:

```bash
composer require symfony/intl
```

Alternatively, PHP’s `ext-intl` extension can be used if available.

---

## Installing the MaxMind GeoLite2 Database

### 1. Create a MaxMind account

Create a free account at:

[https://www.maxmind.com](https://www.maxmind.com)

Generate a **license key** (required even for free GeoLite2 databases).

---

### 2. Download the GeoLite2 City database

You may download the database manually from:

[https://dev.maxmind.com/geoip/geolite2-free-geolocation-data](https://dev.maxmind.com/geoip/geolite2-free-geolocation-data)

Or via CLI:

```bash
mkdir -p storage/app/geoip

curl -L \
  -u "$MAXMIND_ACCOUNT_ID:$MAXMIND_LICENSE_KEY" \
  "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City&suffix=tar.gz" \
| tar -xz --strip-components=1 -C storage/app/geoip
```

After extraction, you should have:

```text
storage/app/geoip/GeoLite2-City.mmdb
```

> **Important:** This file should **not** be committed to version control.

---

## Package Configuration

Enable location enrichment in `config/session-manager.php`:

```php
'location' => [
    'enabled' => true,

    'cache' => [
        'enabled' => true,
        'ttl' => 60 * 60 * 24 * 7, // 7 days
        'store' => null,
    ],

    'maxmind' => [
        'database_path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
        'store_coordinates' => false,
    ],
],
```

### Caching Behavior

* Location lookups are cached **per IP address**
* Cache keys are hashed (IP addresses are not stored in plain text)
* Null results are cached to avoid repeated lookups
* Cache duration is configurable

---

## What Data Is Stored

When enabled, the following fields may be populated on a session:

* Country
* Region / state
* City
* Timezone
* Optional latitude / longitude
* Confidence level (high / medium / low)

All location data is derived from IP geolocation and is **approximate by nature**.

---

## Privacy and Accuracy Notes

* IP geolocation is not precise
* Mobile networks, VPNs, and CGNAT may reduce accuracy
* Location data should be presented as informational only

The package exposes confidence helpers such as:

* `labelWithConfidence()` → `Near Paris, France`
* `isApproximate()`

Use these helpers to present location data responsibly.

---

## Troubleshooting

**Location data is always null**

* Ensure `geoip2/geoip2` is installed
* Verify the `.mmdb` file exists at the configured path
* Confirm the IP address is public (private IPs cannot be resolved)

**Country names are not localized**

* Install `symfony/intl` or enable PHP `ext-intl`

---

## Summary

Location enrichment is:

* Optional
* Local-only (no external API calls)
* Cached
* Privacy-conscious
* Designed for session visibility, not tracking

If you do not need session location data, no configuration is required.
