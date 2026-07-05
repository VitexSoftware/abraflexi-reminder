# AGENTS.md — abraflexi-reminder

## What this project does

Sends payment reminders to customers with overdue invoices. Manages labels on
customer address-book records in AbraFlexi and optionally disconnects customers
from service via a label.

Package: `vitexsoftware/abraflexi-reminder`  
Core class: `AbraFlexi\Reminder\Upominac`  
Notifiers: `src/AbraFlexi/Reminder/Notifier/*.php` — auto-discovered, no registration needed

## Pipeline (MultiFlexi RunTemplates)

| RT | Name | Script | Schedule |
|----|------|--------|----------|
| RT52 | Payment Simulator | `abraflexi-payment-simulator` | hourly |
| RT54 | Matcher | `abraflexi-matcher` | hourly |
| RT53 | Reminder | `abraflexi-reminder` | daily 06:00 |
| RT55 | Clear Labels | `abraflexi-reminder-clean-labels` | daily 07:00 |
| RT56 | Debts Overview | `abraflexi-debts-overview` | daily 08:00 |
| RT57 | Notify Customers | `abraflexi-notify-customers` | daily 09:00 |

## Customer label state machine

This is a **contract interface** shared with `abraflexi-webhook-acceptor` and
`isp-tools`. Do not change label semantics without updating AGENTS.md in both
projects.

| Label | Set by | Cleared by | Read by |
|-------|--------|------------|---------|
| `UPOMINKA1` | Reminder — 1st notice | Clear Labels (after payment) | — |
| `UPOMINKA2` | Reminder — 2nd notice | Clear Labels (after payment) | — |
| `UPOMINKA3` | Reminder — 3rd notice | Clear Labels (after payment) | — |
| `NEPLATIC`  | Reminder — score ≥ 3 | Clear Labels (after payment) | ByServiceToggle |
| `ODPOJENO`  | ByServiceToggle (RT57) | Clear Labels (after payment) | isp-tools |

### ByServiceToggle env vars
- `SERVICE_TOGGLE_ENABLED=true` — opt-in activation (default: false)
- `SERVICE_DISCONNECT_LABEL=ODPOJENO` — label name (default: ODPOJENO)

When `SERVICE_TOGGLE_ENABLED=true`, RT55 (Clear Labels) also removes `ODPOJENO`.

## Critical technical details

### getEvidenceDebts() — pohledavka evidence
Records in the `pohledavka` evidence can have null `typDoklK`. `AbraFlexi\Relation`
throws `TypeError` in `Relation::fromTypDokl(null)`. Therefore:
- `typDokl(typDoklK,kod)` is added to `colsToGet` **only for `faktura-vydana`**
- `includes=/evidence/typDokl` is set **only for `faktura-vydana`**
- Always use `isset($invoiceData['typDokl'])` before accessing the key

### Customer score
`score` = integer weeks overdue. Threshold for `NEPLATIC` and `ByServiceToggle`
activation is `score >= 3`.

### Notifier interface
```php
interface notifier {
    public function compile(int $score, Customer $customer, array $clientDebts): bool;
}
```
Every class in namespace `AbraFlexi\Reminder\Notifier\` is automatically
instantiated in `processNotifyModules()`.

### MUTE mode
`MUTE=true` — reminder sets labels but sends no emails or SMS.
Always set in CI and test environments.

## Environment variables

| Variable | Description |
|----------|-------------|
| `ABRAFLEXI_URL` | AbraFlexi server URL |
| `ABRAFLEXI_LOGIN` | AbraFlexi login |
| `ABRAFLEXI_PASSWORD` | AbraFlexi password |
| `ABRAFLEXI_COMPANY` | Company code in AbraFlexi |
| `REMIND_FROM` | Sender email address for reminders |
| `MUTE` | `true` = label-only mode, no sending |
| `OVERDUE_PATIENCE` | Tolerance days after due date (default: 0) |
| `REMINDER_SKIPDOCTYPE` | Comma-separated document types to skip |
| `SERVICE_TOGGLE_ENABLED` | `true` = activate ByServiceToggle |
| `SERVICE_DISCONNECT_LABEL` | Disconnect label name (default: `ODPOJENO`) |

## Test environment

- AbraFlexi: `https://flexibee-dev.spoje.net:5434`, company `spoje_net_s_r_o_`
- Credentials: `admin:wiwobr=metCob5`
- MultiFlexi: `https://vyvojar.spoje.net/multiflexi/`
- **Never use production credentials from `.env`** against the test server

## Debian packaging

```bash
fakeroot debian/rules clean
dpkg-buildpackage -us -uc -b
# result: ../abraflexi-reminder_X.Y.Z_all.deb
scp ../abraflexi-reminder_*.deb vyvojar.spoje.net:/tmp/
ssh vyvojar.spoje.net 'sudo dpkg -i /tmp/abraflexi-reminder_*.deb'
```

Version lives in `debian/changelog`. Always update `CHANGELOG.md` alongside it.

## Compatibility with abraflexi-webhook-acceptor

The webhook acceptor (`VitexSoftware/abraflexi-webhook-acceptor`) captures
changes from the AbraFlexi Changes API. If you add a new label or change the
semantics of an existing one, update AGENTS.md in both projects simultaneously.
