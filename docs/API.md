# API Documentation

Internal API and extension points for AbraFlexi Reminder.

## Architecture Overview

```
src/
├── abraflexi-reminder.php            # Main entry point: process all debts and send reminders
├── abraflexi-show-debts.php          # List overdue invoices and write JSON report
├── abraflexi-reminder-clean-labels.php  # Remove reminder labels from fully-paid customers
├── abraflexi-inventarize-customers.php  # Customer obligation overview
├── init.php                          # Create required AbraFlexi labels on first run
└── AbraFlexi/Reminder/
    ├── Upominac.php                  # Main reminder processor
    ├── Upominka.php                  # Template loader and shared helpers
    ├── RemindMailer.php              # HTML mailer with PDF attachment support
    ├── PDFPage.php                   # PDF generation for paper mail
    ├── CompanyLogo.php               # Company logo embedding
    ├── notifier.php                  # Notifier interface
    ├── Sms.php / SmsToAddress.php    # Base SMS classes
    ├── SmsByGnokii.php               # Gnokii SMS backend
    ├── SmsBySshGnokii.php            # SSH-tunnelled Gnokii backend
    ├── SmsByHuaweiApi.php            # Huawei E5180 LTE modem backend
    ├── InvoiceRecievedConfirmation.php
    ├── InvoiceRecievingConfirmation.php
    ├── PaymentRecievedConfirmation.php
    ├── SentPaymentConfirmation.php
    └── Notifier/
        ├── ByEmail.php               # E-mail notification (PDF + ISDOCx attachments)
        ├── BySms.php                 # SMS notification
        └── ByDatovka.php             # Czech Data Box (ISDS) notification
```

## Core Classes

### `Upominac` — Main Reminder Processor

Extends `\AbraFlexi\RW`. Orchestrates the full reminder pipeline.

| Method | Description |
|--------|-------------|
| `getAllDebts(array $conditions): array` | Fetch all overdue invoices from `faktura-vydana` and `pohledavka` |
| `getCustomerList(array $conditions): array` | Fetch all customers indexed by code |
| `getClientsToSkip(array $allClients, self $reminder): array` | Return clients bearing the `NO_REMIND_LABEL` label |
| `prepareDebts(array $allDebts, array &$allClients, array $clientsToSkip, self $reminder): array` | Group debts by client, compute per-client totals |
| `processDebts(array $allDebtsByClient, array $allClients, array $clientsToSkip, self $reminder, array $report): array` | Invoke `processUserDebts()` for each client |
| `processUserDebts(array $clientInfo, array $clientDebts): array` | Determine reminder level, send notification, update invoice dates and labels |
| `getEvidenceDebts(string $evidence, array $conditions): array` | Fetch overdue invoices from one AbraFlexi evidence |
| `getCustomersDebts(array $skipLabels, bool $cleanLabels): array` | Collect all debts grouped by customer (legacy path) |
| `formatTotals(array $totals): string` | Format a `[currency => amount]` map as a human-readable string |
| `getDaysToLastInventarization(array $clientDebts): int` | Days since the most recent reminder or inventarization date |
| `logBanner(?string $prefix, ?string $suffix): void` | Log application name and version banner |

**Reminder levels** (returned by the private `calculateReminderLevel()`):

| Level | Condition |
|-------|-----------|
| 1 | Overdue 1–7 days, `UPOMINKA1` not yet set |
| 2 | Overdue 8–14 days, `UPOMINKA1` set, `UPOMINKA2` not yet set |
| 3 | Overdue > 14 days, `UPOMINKA1` and `UPOMINKA2` set |

**AbraFlexi labels** managed by the system:

| Label | Meaning |
|-------|---------|
| `UPOMINKA1` | First reminder sent |
| `UPOMINKA2` | Second reminder sent |
| `UPOMINKA3` | Third / final reminder sent |
| `NEPLATIC` | Non-payer (informational) |
| `NEUPOMINAT` | Do not send reminders to this customer |
| `DATA_BOX` | Send reminders via Czech Data Box |

---

### `Upominka` — Template Loader and Helpers

Extends `\AbraFlexi\RW` against the `sablona-upominky` evidence.

| Method | Description |
|--------|-------------|
| `loadTemplate(string $template): void` | Load a named template (`prvniUpominka`, `druhaUpominka`, `pokusOSmir`, `inventarizace`) |
| `static debtAmount(array $debt): float` | Return the amount still owed, using `zbyvaUhradit` for CZK or `zbyvaUhraditMen` for foreign currency |
| `static getSums(array $debts): array` | Sum debt amounts by currency code |
| `static qrPayments(array $debts): DivTag` | Build an HTML block of QR payment images |
| `static formatCurrency(float $price): string` | Format a number as Czech currency (`1 234,56`) |
| `static $styles: string` | CSS for the debt table embedded in reminder e-mails |

---

### `RemindMailer` — HTML Mailer

Extends `\Ease\HtmlMailer`. Builds and sends HTML reminder e-mails.

| Method | Description |
|--------|-------------|
| `addFile(string $filename, string $mimeType): bool` | Attach a file; returns `true` only if this file was successfully attached |
| `getCurrentMailSize(): int` | Return current serialized mail size in bytes |
| `send(): bool` | Send the e-mail and delete all temporary attachment files |

---

### Notifier Interface

Every notifier class lives in `AbraFlexi\Reminder\Notifier\`, implements `AbraFlexi\Reminder\notifier`, and is instantiated automatically by `Upominac::processNotifyModules()`.

Constructor signature:

```php
public function __construct(Upominac $reminder, int $score, array $debts)
```

The constructor must perform the full notification (compile + send) and store the outcome in `$this->result`.

#### Creating a Custom Notifier

```php
<?php

declare(strict_types=1);

namespace AbraFlexi\Reminder\Notifier;

use AbraFlexi\Reminder\Upominac;
use Ease\Sand;

class ByWebhook extends Sand implements \AbraFlexi\Reminder\notifier
{
    public array $result = [];

    public function __construct(Upominac $reminder, int $score, array $debts)
    {
        $this->setObjectName();
        $sent = false;
        $webhookUrl = \Ease\Shared::cfg('WEBHOOK_URL', '');

        if ($webhookUrl) {
            // compile payload and POST to $webhookUrl …
            $sent = true;
        }

        $this->result = ['sent' => $sent, 'message' => $sent ? _('Webhook sent') : _('Webhook skipped')];
    }
}
```

Drop the file into `src/AbraFlexi/Reminder/Notifier/` — it is loaded automatically at runtime.

---

## AbraFlexi Evidence Endpoints Used

| Evidence | Usage |
|----------|-------|
| `faktura-vydana` | Issued invoices (primary debt source) |
| `pohledavka` | Receivables (secondary debt source) |
| `adresar` | Address book / customer data |
| `sablona-upominky` | Reminder e-mail templates |

## Error Handling

All operations log status messages via `addStatusMessage()` with levels `debug`, `info`, `success`, `warning`, or `error`. The exit code of the main scripts reflects the outcome:

| Exit code | Meaning |
|-----------|---------|
| `0` | Success |
| `1` | Warning (e.g. no debts found, file write failed) |
| `2` | Error |

## Testing

```bash
vendor/bin/phpunit
```

Network-dependent tests require a reachable AbraFlexi instance configured in `.env`. Pure-logic tests (e.g. `debtAmount`, `getSums`, `formatTotals`) run without a live connection.
