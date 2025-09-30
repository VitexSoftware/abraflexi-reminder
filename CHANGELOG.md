# Changelog

All notable changes to AbraFlexi Reminder will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.7.3] - 2025-09-30

### Fixed
- **MultiFlexi JSON Schema Compliance**: Fixed validation errors in MultiFlexi application JSON files
  - Changed `APP_DEBUG.type` from `"boolean"` to `"bool"` in `abraflexi-reminder.multiflexi.app.json`
  - Changed `SURRENDER_DAYS.type` from `"number"` to `"integer"` in `abraflexi-reminder.multiflexi.app.json`
  - Changed `OVERDUE_PATIENCE.type` from `"number"` to `"integer"` in `inventarize.multiflexi.app.json`
- All MultiFlexi JSON files now validate against schema version 2.1.1

### Improved
- Updated documentation with comprehensive configuration examples
- Added troubleshooting section for common issues
- Enhanced MultiFlexi integration documentation
- Added command usage examples and descriptions

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