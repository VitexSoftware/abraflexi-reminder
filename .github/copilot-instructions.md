---
description: AbraFlexi Reminder - automated reminder system for AbraFlexi accounting software
applyTo: '**'
---

# AbraFlexi Reminder - Copilot Instructions

## Project Overview
AbraFlexi Reminder is an **automated reminder system** for AbraFlexi accounting software:
- **Invoice Reminders**: Automated overdue invoice notifications
- **Payment Tracking**: Payment reminder system
- **AbraFlexi Integration**: Direct API integration with accounting system
- **MultiFlexi Framework**: Built using VitexSoftware MultiFlexi ecosystem
- **Email Templates**: Customizable reminder email templates

## 📋 Development Standards

### Core Coding Guidelines
- **PHP 8.4+**: Use modern PHP features and strict types: `declare(strict_types=1);`
- **PSR-12**: Follow PHP-FIG coding standards for consistency
- **Type Safety**: Include type hints for all parameters and return types
- **Documentation**: PHPDoc blocks for all public methods and classes
- **Testing**: PHPUnit tests for all new functionality
- **Internationalization**: Use `_()` functions for translatable strings

### Code Quality Requirements
- **Syntax Validation**: After every PHP file edit, run `php -l filename.php` for syntax checking
- **Error Handling**: Implement comprehensive try-catch blocks with meaningful error messages
- **Testing**: Create/update PHPUnit test files for all new/modified classes
- **Performance**: Optimize for production use with large datasets
- **Security**: Ensure code doesn't expose sensitive information

### Development Best Practices
- **Code Comments**: Write in English using complete sentences and proper grammar
- **Variable Names**: Use meaningful names that describe their purpose
- **Constants**: Avoid magic numbers/strings; define constants instead
- **Exception Handling**: Always provide meaningful error messages
- **Commit Messages**: Use imperative mood and keep them concise
- **Security**: Ensure code is secure and doesn't expose sensitive information
- **Compatibility**: Maintain compatibility with latest PHP and library versions
- **Maintainability**: Follow best practices for maintainable code

### MultiFlexi Integration Guidelines
- **Schema Compliance**: All MultiFlexi JSON files must conform to official schemas
- **Application Config** (`multiflexi/*.app.json`): 
  https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.app.schema.json
- **Report Output**: 
  https://raw.githubusercontent.com/VitexSoftware/php-vitexsoftware-multiflexi-core/refs/heads/main/multiflexi.report.schema.json

### AbraFlexi Integration Requirements
- **API Authentication**: Secure authentication with AbraFlexi systems
- **Data Privacy**: Handle sensitive financial data appropriately
- **Error Recovery**: Implement retry logic for network failures
- **Transaction Safety**: Ensure data integrity in all operations

### Testing Requirements
- **PHPUnit Integration**: All new classes require corresponding test files
- **Test Coverage**: Aim for comprehensive test coverage of all functionality
- **Mock AbraFlexi**: Use mocks for AbraFlexi API during testing
- **Email Testing**: Test email functionality without sending real emails

## Example Commands
```bash
# Syntax check
php -l src/ReminderService.php

# Run tests
vendor/bin/phpunit tests/

# Validate MultiFlexi config
multiflexi-cli application validate-json --file multiflexi/reminder.app.json
```

⚠️ **Important Notes for Copilot:**
- This handles **sensitive financial data** - prioritize security in all operations
- **Email reliability** is critical for business operations
- Follow **MultiFlexi ecosystem patterns** for consistency
- **AbraFlexi API changes** may require updates - monitor compatibility
- All reminder logic must be **thoroughly tested** before deployment
