# Environment Variables

All configuration is supplied through environment variables (or a `.env` file passed at startup).

## AbraFlexi Connection

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `ABRAFLEXI_URL` | string | Yes | | AbraFlexi server URI, e.g. `https://demo.flexibee.eu:5434` |
| `ABRAFLEXI_LOGIN` | string | Yes | | AbraFlexi login |
| `ABRAFLEXI_PASSWORD` | password | Yes | | AbraFlexi password |
| `ABRAFLEXI_COMPANY` | string | Yes | | AbraFlexi company identifier |

## Mail

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `REMIND_FROM` | email | Yes | | Sender address for reminder e-mails |
| `MAIL_CC` | email | No | | CC address added to every reminder e-mail |
| `MUTE` | bool | No | `false` | When `true`, redirect all e-mails to `EASE_EMAILTO` instead of the real customer address |
| `EASE_EMAILTO` | email | No | | Fallback address used when `MUTE` is `true` |
| `MAX_MAIL_SIZE` | integer | No | `0` | Maximum total e-mail size in bytes; attachments skipped when exceeded (0 = unlimited) |
| `ADD_LOGO` | bool | No | `false` | Embed company logo in reminder e-mails |
| `QR_PAYMENTS` | bool | No | `false` | Attach QR payment codes to reminder e-mails |

## SMS

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `SMS_ENGINE` | string | No | | SMS backend: `gnokii`, `sshgnokii`, or `huaweiapi` |
| `SMS_SENDER` | string | No | | Sender name shown in SMS |
| `SMS_SIGNATURE` | string | No | | Signature appended to every SMS |
| `GNOKII_HOST` | string | No | | SSH host for `sshgnokii` engine |
| `MODEM_IP` | string | No | `192.168.8.10` | IP of Huawei E5180 modem |
| `MODEM_PASSWORD` | string | No | | Password for Huawei E5180 modem |

## Czech Data Box (Datová schránka)

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `DATOVKA_LOGIN` | string | No | | Data Box login |
| `DATOVKA_PASSWORD` | password | No | | Data Box password |

## Reminder Logic

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `SURRENDER_DAYS` | integer | No | `365` | Ignore invoices overdue by more than this many days |
| `OVERDUE_PATIENCE` | integer | No | | Grace days before an overdue invoice triggers a reminder |
| `NO_REMIND_LABEL` | string | No | `NEUPOMINAT` | AbraFlexi label that suppresses reminders for a customer or document |
| `REMINDER_SKIPDOCTYPE` | string | No | | Comma-separated document type codes to skip (e.g. `DOBROPIS,ZDD`) |
| `SEND_INFO_TO` | email | No | | Send a summary report to this address after each run |

## Output & Logging

| Variable | Type | Required | Default | Description |
|----------|------|----------|---------|-------------|
| `RESULT_FILE` | string | No | `php://stdout` | Path to write the JSON result report |
| `LANG` | string | No | `cs_CZ` | Locale used for translated messages |
| `APP_DEBUG` | bool | No | `false` | Enable debug-level log output and banner |
| `DEBUG` | bool | No | `false` | Enable verbose internal debugging |
