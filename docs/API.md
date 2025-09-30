# API Documentation

This document describes the internal API and extension points for AbraFlexi Reminder.

## Architecture Overview

AbraFlexi Reminder follows a modular architecture with the following main components:

```
src/AbraFlexi/Reminder/
├── Upominac.php          # Main reminder processor
├── Customer.php          # Customer handling
├── Inventarize.php       # Customer obligation overview
├── Notifier/             # Notification modules
│   ├── ByEmail.php       # Email notifications
│   ├── BySMS.php         # SMS notifications
│   └── ByDataBox.php     # Czech DataBox notifications
└── Reminder/
    ├── Init.php          # System initialization
    └── Labels.php        # Label management
```

## Extending the System

### Creating Custom Notifiers

To create a custom notification method, implement a class in the `src/AbraFlexi/Reminder/Notifier/` directory:

```php
<?php

declare(strict_types=1);

namespace AbraFlexi\Reminder\Notifier;

use AbraFlexi\Reminder\Customer;

/**
 * Custom notification example
 */
class ByCustomMethod extends \AbraFlexi\RO
{
    /**
     * Send notification to customer
     *
     * @param Customer $customer Customer to notify
     * @param array $invoices Array of overdue invoices
     * @return bool Success status
     */
    public function send(Customer $customer, array $invoices): bool
    {
        // Implement your custom notification logic here
        $this->addStatusMessage(sprintf(
            _('Custom notification sent to %s'),
            $customer->getEmail()
        ), 'success');
        
        return true;
    }
}
```

### Configuration Integration

Custom notifiers are automatically loaded if they follow the naming convention `By[MethodName].php` and implement the required `send()` method.

## Core Classes

### Upominac (Main Reminder Class)

The main class responsible for processing reminders:

- `loadCustomers()` - Load customers with overdue invoices
- `processCustomers()` - Process each customer and send notifications
- `sendReminder($customer)` - Send reminder to specific customer

### Customer Class

Extended customer handling with reminder-specific functionality:

- `getOverdueInvoices()` - Get list of overdue invoices
- `hasLabel($label)` - Check if customer has specific label
- `addLabel($label)` - Add label to customer
- `removeLabel($label)` - Remove label from customer

### Notification Flow

1. **Initialize** - Create required labels and setup
2. **Load Customers** - Find customers with overdue invoices
3. **Process Labels** - Clean up labels for paid customers
4. **Send Notifications** - Use configured notifiers to send reminders
5. **Update Labels** - Mark customers with appropriate reminder level
6. **Generate Reports** - Create JSON reports if configured

## MultiFlexi Integration

### Application Structure

Each MultiFlexi application is defined by a JSON file following the schema:

```json
{
    "name": "Application Name",
    "description": "Brief description",
    "executable": "command-to-run",
    "setup": "initialization-command", 
    "environment": {
        "VARIABLE_NAME": {
            "type": "string|integer|bool|email|url|set|password|text|file-path|float",
            "description": "Variable description",
            "defval": "default-value",
            "required": true|false
        }
    }
}
```

### Available Applications

- **Reminder** - Main payment reminder sender
- **Inventarize** - Customer obligation overview
- **Clean Labels** - Remove outdated reminder labels  
- **Show Debts** - Generate debts report

## Database Integration

### Labels Used

- `UPOMINKA1` - First reminder sent
- `UPOMINKA2` - Second reminder sent  
- `UPOMINKA3` - Third reminder sent (final notice)
- `NEPLATIC` - Non-payer (informational)
- `NEUPOMINAT` - Don't send reminders
- `DATA_BOX` - Customer has DataBox for ISDS delivery

### AbraFlexi Integration

The system integrates with these AbraFlexi endpoints:

- `faktura-vydana` - Issued invoices
- `pohledavka` - Receivables
- `adresar` - Address book
- `sablona-upominky` - Reminder templates

## Environment Configuration

All configuration is handled through environment variables or JSON config files. See [environment-variables.md](environment-variables.md) for complete reference.

## Error Handling

The system uses structured error handling with:

- **Logging** - All operations are logged using configurable loggers
- **Validation** - Input validation for all configuration parameters
- **Graceful Degradation** - Continue processing other customers if one fails
- **Reporting** - Detailed status reports in JSON format

## Testing

Run the test suite:

```bash
composer test
phpunit
```

For development testing, use the MUTE option to send all notifications to admin email instead of customers.