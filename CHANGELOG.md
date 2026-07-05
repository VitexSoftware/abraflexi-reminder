# Changelog

All notable changes to AbraFlexi Reminder will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.5] - 2026-07-05

### Fixed
- `abraflexi-reminder-clean-labels`: when `SERVICE_TOGGLE_ENABLED=true`, the configured `SERVICE_DISCONNECT_LABEL` (default `ODPOJENO`) is now also removed from customers who have no remaining debts — completing the reconnect half of the `ByServiceToggle` cycle

## [1.7.4] - 2026-07-05

### Fixed
- `Upominac::getEvidenceDebts()`: `typDokl(typDoklK,kod)` is now only fetched when evidence is `faktura-vydana`; `pohledavka` records in AbraFlexi can have a null `typDoklK` which previously caused `AbraFlexi\Relation::__construct()` to throw a fatal `TypeError` in the Notify Customers step
- `Upominac::getEvidenceDebts()`: guard `$invoiceData['typDokl']` access with `isset()` before the `REMINDER_SKIPDOCTYPE` check to suppress PHP warnings for evidences that do not carry `typDokl`
- `debian/rules`: replaced five broken `sed` patterns that used unescaped `.` (matches any char) with literal-string patterns using `|` delimiter — previously corrupted every installed PHP file on package install
- `debian/autoload.php`: removed `declare(strict_types=1)` — `debian/rules` inserts `defined('APP_NAME')` after line 1 via `sed "1a"`, pushing the declaration to line 5 where PHP rejects it

### Added
- `Notifier/ByServiceToggle`: new notifier that sets/clears a `SERVICE_DISCONNECT_LABEL` (default `ODPOJENO`) on customers based on the `NEPLATIC` label — bridges the reminder pipeline to ISP disconnection tooling

### Tests
- Implemented `UpominacTest::testGetEvidenceDebts()`: verifies both `faktura-vydana` and `pohledavka` evidence return arrays without TypeError
- Implemented `UpominacTest::testGetAllDebts()`: verifies merged debt array contains `firma` and `zbyvaUhradit` keys

## [1.7.3] - 2025-05-20

### Fixed
- `ByDatovka`: logic error in config check `empty(A && B)` — now correctly detects missing credentials
- `ByDatovka`: null-dereference on `$this->reminder` replaced with `$reminder` parameter
- `ByDatovka`: `$result` used before assignment when `login()` returned false
- `ByDatovka`: unchecked `curl_exec()` result passed to `SimpleXMLElement` caused fatal crash on network failure
- `ByDatovka`: added `CURLOPT_TIMEOUT` (10 s) and `CURLOPT_CONNECTTIMEOUT` (5 s) to prevent indefinite hangs
- `ByDatovka`: empty error handler in `send()` replaced with status message logging
- `ByDatovka`: TOCTOU `file_exists/mkdir` replaced with `!is_dir/mkdir(..., 0775, true)`
- `RemindMailer::addFile()`: returned cumulative non-emptiness instead of per-call result
- `init.php`: `APP_DEBUG` comparison was case-sensitive (`'True'`); now uses `strtolower()`

### Changed
- Extracted `Upominka::debtAmount(array $debt): float` — replaces five duplicate CZK/foreign currency resolution blocks
- Replaced `Upominac::maxScore()` private wrapper with PHP built-in `max()`
- `abraflexi-show-debts.php`: replaced manual skip-clients loop with `getClientsToSkip()`
- `ByEmail::addAttachments()`: hoisted `MAX_MAIL_SIZE` config lookup out of the per-debt loop
- Removed dead `mbstring.func_overload` branch in `RemindMailer::getCurrentMailSize()` (removed in PHP 8)

### Removed
- Dead variables `$invoices = []` in `ByEmail::compile()` and `ByDatovka::compile()`
- Dead `$howmuchRaw`/`$howmuch` accumulator block in `abraflexi-show-debts.php`
- Commented-out debug lines in `BySms` and `Upominac`
- Commented-out `$simpleApi` property block in `ByDatovka`

### Packaging
- `debian/rules`: added `PKG_VERSION`/`PKG_SOURCE`/`PKG_TYPE` Make vars; replaced all inline `dpkg-parsechangelog | sed` invocations
- `debian/autoload.php`: added `InstalledVersions::reload()` block with build-time placeholders baked in by `debian/rules`
- `debian/control`: added `appstream` to `Build-Depends`
- AppStream metainfo: fixed type `service` → `console-application`; added `<url>`, `<developer>`, `<releases>`, `<content_rating>`, `<provides>`; passes `appstreamcli validate --pedantic`
- Added man pages for `abraflexi-reminder-init` and `abraflexi-reminder-clean-labels`

## [1.7.2] - 2025-05-03

### Changed
- Code updates and improvements

## [1.7.1] - 2025-02-27

### Changed
- Updated for current php-abraflexi version
- AbraFlexi logo updates

## [1.7.0] - 2024-09-11

### Added
- Strict type declarations implemented across codebase

### Changed
- Improved code reliability and performance with strict typing

## [1.6.6] - 2024-08-XX

### Added
- Support for environment-based configuration
- Enhanced configuration flexibility

## Previous Versions

For historical changes before version 1.6.6, please refer to the [debian/changelog](debian/changelog) file.

---

## Schema Compliance Notes

### MultiFlexi JSON Schema Types

The MultiFlexi platform requires environment variable types to be from the following enumeration:
- `string` - Text values
- `file-path` - File system paths  
- `email` - Email addresses
- `url` - Web URLs
- `integer` - Whole numbers
- `float` - Decimal numbers
- `bool` - Boolean true/false values
- `password` - Sensitive text (masked in UI)
- `set` - Selection from predefined options
- `text` - Multi-line text

**Important**: Do not use `boolean` (use `bool`) or `number` (use `integer` or `float`) as these are not valid in the schema.