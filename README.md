AbraFlexi Reminder / Odesílač upomínek pro AbraFlexi
=====================================================

![Package Logo](abraflexi-reminder.svg?raw=true "Project Logo")

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![MultiFlexi Ready](https://img.shields.io/badge/MultiFlexi-ready-green.svg)](https://www.multiflexi.eu/)

A comprehensive reminder system for AbraFlexi that automatically sends payment reminders to customers with overdue invoices. Supports multiple notification methods including email, SMS, and Czech data boxes (ISDS).

## Key Features

* **PDF and ISDOC attachments** - Complete invoice documents
* **QR Payment codes** - Automatic QR code generation for easy payments (optional)
* **Extensible notification system** - Add custom modules for SMS sending or service disconnection
* **Configurable email size limits** - Prevent large emails from being rejected
* **Document type blacklisting** - Skip specific document types (e.g., credit notes)
* **Label-based control** - Use labels like "NEUPOMINKOVAT" (DON'T REMIND) for fine-grained control
* **Multi-currency support** - Handle invoices in foreign currencies
* **Company logo integration** - Include your company logo in reminders
* **Internationalization** - Czech and English localization using gettext
* **Multiple deployment options** - Debian/Ubuntu packages, Docker, or manual installation
* **PHP 8.2+ compatibility** - Modern PHP support with strict typing

Příkaz **abraflexi-show-debts** pouze vypíše pohledávky dle jednotlivých dlužníků (export do json).

Příkaz **abraflexi-reminder** Po spuštění (vytvoří potřebné štítky a)
zkontroluje v přednastavené firmě pohledávky. Při odeslání upomínky
Pokud nemá zákazník nastaven štítek NEUPOMINKOVAT, je mu odeslána upomínka.
příkaz je určen k automatickému spouštění každý den.

![Upomínka](reminder-screenshot.png?raw=true "ukázka upomínky")

Příkaz **abraflexi-inventarize** zašle klientům přehled jejich závazků.
Předpokládá se jeho automatické spouštění jednou za měsíc.

Prohledávají se evidence "vydané faktury" a "pohledávky"

Funkce štítků
-------------

Štítky mají jak informativní tak řídící funkci. Po spuštění upomínkovače se nejprve se projdou všichni klienti a těm kteří nemají žádní neuhrazené pohledávky jsou odstraněny štítky  UPOMINKA1,UPOMINKA2,UPOMINKA3 a NEPLATIC.
Datum odeslání upomínky je zapisováno do jednotlivé faktury do sloupců `datUp1`,`datUp2` a `datSmir` - [více sloupců ve faktuře abraflexi na to není](https://demo.flexibee.eu/c/demo/faktura-vydana/properties).
Avšak upomínaný je klient ne faktura a tuto skutečnost je třeba nějakým způsobem poznamenat. To se děje právě prostřednictvím štítku.
Tzn. pokud má klient nastavený štítek `UPOMINKA1` a `UPOMINKA2` znamená to, že klientovi byly již odeslány dvě upomínky. Pro program to znamená že další odeslaná upomínka již bude pokus o smír.
Současně je také informace o tom že upomínka byla opravdu odeslána. tzn. nenastaví se v případě že na zákazníka není znám email, nebo že poštovní server zrovna někdo rebootoval.
Další týden po odeslání třetí upomínky se klientovi nastaví informativní štítek `NEPLATIC`

Upomínka Mailem
---------------

Texty upomínek se mění ve abraflexi evidenci **sablona-upominky**
A je poznamenán datum jejího odeslání a současně je zákazníkovi přiřazen štítek `UPOMINKA1-3`
Odeslaná upomínka obsahuje přehled všech položek po splatnosti a k nim patřičné přílohy ve formátech pdf a isdocx

Upomínka SMS
------------

Upomínky je v současné době možné zasílat jako SMS prostřednictvím těchto metod:

* **místní gnokii** - na stejném stroji kde běží upomínkovač je nainstalována aplikace gnokii.
* **vzdálené gnokii** - gnokii je nainstalována na jiném stroji. Příkaz na něm je spouštěn prostřednictvím SSH s klíčem
* **Axfone SMS brána** - Vaše přihlašovací údaje zadejte do konfiguráku pod klíči **AXFONE_USERNAME** a **AXFONE_PASSWORD**
* **Huawei E5180 API** - Nastavte **MODEM_PASSWORD** (případně **MODEM_IP** pokud se liší od 192.168.8.1)

![SMS Upomínka](reminder-sms-screenshot.png?raw=true "ukázka SMS upomínky")

jiná akce při upomínání
-----------------------

Do složky  **src/AbraFlexi/Reminder/Notifier** ( /usr/lib/abraflexi-reminder/Reminder/Notifier/ v případě instalace z debianího balíčku )
je možné přidat další moduly vykonávající akci. Například odpojení neplatiče od služby atd.
Jak takové doplňky psát by mělo být zřejmé z [ByEmail.php](src/AbraFlexi/Reminder/Notifier/ByEmail.php)

## Installation / Instalace

### Debian/Ubuntu Packages

Pre-built .deb packages are available from the VitexSoftware repository:

```bash
sudo apt install lsb-release wget
echo "deb http://repo.vitexsoftware.com $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/vitexsoftware.list
sudo wget -O /etc/apt/trusted.gpg.d/vitexsoftware.gpg http://repo.vitexsoftware.cz/keyring.gpg
sudo apt update
sudo apt install abraflexi-reminder
```

### Docker Installation

```bash
# Pull the image
docker pull vitexsoftware/abraflexi-reminder

# Run with environment configuration
docker run -d \
  --name abraflexi-reminder \
  --env-file .env \
  vitexsoftware/abraflexi-reminder
```

### Composer Installation

```bash
composer create-project vitexsoftware/abraflexi-reminder
cd abraflexi-reminder
cp example.env .env
# Edit .env with your configuration
./bin/abraflexi-reminder-init
```

### MultiFlexi Installation

1. Access your MultiFlexi dashboard
2. Go to Applications → Add Application
3. Search for "AbraFlexi Reminder"
4. Click Install and configure environment variables
5. Set up scheduling for automatic execution

After installation, the following commands are available:

| Command | Purpose | Description |
|---------|---------|-------------|
| `abraflexi-show-debts` | **Debt Overview** | List all found receivables in JSON format |
| `abraflexi-reminder` | **Send Reminders** | Main command to send payment reminders to debtors |
| `abraflexi-notify-customers` | **Customer Notifications** | Send customers a list of their obligations (inventarization) |
| `abraflexi-reminder-init` | **Initialize Setup** | Prepare the default company for use with the reminder system |

### Command Examples

```bash
# Initialize the system (create required labels)
abraflexi-reminder-init

# Show current debts without sending reminders
abraflexi-show-debts

# Send reminders (production mode)
abraflexi-reminder

# Test mode - send all notifications to admin email
MUTE=true abraflexi-reminder

# Send monthly inventarization to customers
abraflexi-notify-customers
```

## Configuration / Konfigurace

The application can be configured using:
1. Configuration file: `/etc/abraflexi/reminder.json`
2. Environment variables (recommended for containers and MultiFlexi)
3. Command line parameters

### Environment Variables

Copy and modify the `example.env` file to set up your configuration:

```bash
# Application Settings
APP_DEBUG=false
EASE_LOGGER=syslog
LANG=cs_CZ

# AbraFlexi Connection
ABRAFLEXI_URL=https://demo.flexibee.eu:5434
ABRAFLEXI_LOGIN=winstrom
ABRAFLEXI_PASSWORD=winstrom
ABRAFLEXI_COMPANY=demo_de

# Email Configuration
REMIND_FROM=noreply@yourdomain.net
EASE_MAILTO=info@yourdomain.net
MAIL_CC=accounting@yourdomain.net
MAX_MAIL_SIZE=1250000

# Reminder Settings
QR_PAYMENTS=true
ADD_LOGO=true
SURRENDER_DAYS=365
NO_REMIND_LABEL=NEUPOMINAT
REMINDER_SKIPDOCTYPE=DOBROPIS,ZDD
MUTE=false

# SMS Configuration
SMS_SENDER=+420739778202
SMS_ENGINE=gnokii
SMS_SIGNATURE=Your Company
GNOKII_HOST=sms@your-server.com

# Data Box (ISDS) Configuration
DATOVKA_LOGIN=your_databox_login
DATOVKA_PASSWORD=your_databox_password

# Huawei Modem Settings
MODEM_IP=192.168.8.1
MODEM_PASSWORD=admin

# Output Settings
RESULT_FILE=reminder_{ABRAFLEXI_COMPANY}.json
```

### Configuration Parameters

#### Core Settings
* **APP_DEBUG** - Enable debug mode (bool, default: false)
* **LANG** - Application locale (cs_CZ or en_US, default: cs_CZ)
* **EASE_LOGGER** - Logging method (memory|console|file|syslog|email|std|eventlog)

#### AbraFlexi Connection
* **ABRAFLEXI_URL** - AbraFlexi server URL (required)
* **ABRAFLEXI_LOGIN** - AbraFlexi username (required)
* **ABRAFLEXI_PASSWORD** - AbraFlexi password (required)
* **ABRAFLEXI_COMPANY** - Company database name (required)

#### Email Configuration
* **REMIND_FROM** - Sender email address for reminders (required)
* **EASE_MAILTO** - Email for logs and reports when MUTE is enabled
* **MAIL_CC** - Send copy of each reminder to this email
* **MAX_MAIL_SIZE** - Maximum email size in bytes (default: 1250000 = ~10MB)

#### Reminder Behavior
* **QR_PAYMENTS** - Include QR payment codes in reminders (bool, default: true)
* **ADD_LOGO** - Include company logo in reminders (bool, default: true)
* **SURRENDER_DAYS** - Skip processing cases older than this many days (integer, default: 365)
* **REMINDER_SKIPDOCTYPE** - Comma-separated list of document types to ignore (e.g., "DOBROPIS,ZDD")
* **NO_REMIND_LABEL** - Label to skip reminders (default: "NEUPOMINAT")
* **MUTE** - Test mode - send notifications to EASE_MAILTO instead of customers (bool, default: false)

#### SMS Configuration
* **SMS_ENGINE** - SMS sending method: none|gnokii|sshgnokii|axfone|huaweiapi
* **SMS_SENDER** - Sender phone number (e.g., +420739778202)
* **SMS_SIGNATURE** - Signature appended to SMS messages
* **GNOKII_HOST** - Remote gnokii server (format: user@host)
* **AXFONE_USERNAME** - Axfone API username
* **AXFONE_PASSWORD** - Axfone API password
* **MODEM_IP** - Huawei modem IP address (default: 192.168.8.1)
* **MODEM_PASSWORD** - Huawei modem web interface password

#### Data Box (ISDS) Configuration
* **DATOVKA_LOGIN** - Czech Data Box login
* **DATOVKA_PASSWORD** - Czech Data Box password

#### Output Settings
* **RESULT_FILE** - JSON report output file (default: reminder_{ABRAFLEXI_COMPANY}.json)

V případě že nepoužíváte debianí balíček ale pouze klonujete repozitář, je potřeba před prvním použitím spustit [skript Init.php](src/Init.php) který vytvoří štítky 'UPOMINKA1', 'UPOMINKA2', 'UPOMINKA3', 'NEPLATIC', 'NEUPOMINKOVAT'

ISDS Datové schránky
--------------------

* **DATOVKA_LOGIN** - Přihlašovací jméno do datovky
* **DATOVKA_PASSWORD** - Login do datovky

Upomínky a inventarizace jsou odesílány i do datovky pokud je tato nastavena v konfiguraci a dlužík má v adresáři nastavený štítek `DATA_BOX`

## Dependencies / Závislosti

This application requires the following PHP libraries:

| Library | Version | Purpose |
|---------|---------|---------|
| [**PHP AbraFlexi**](https://github.com/Spoje-NET/php-abraflexi) | ^3.6 | Communication with AbraFlexi ERP system |
| [**AbraFlexi Bricks**](https://github.com/VitexSoftware/php-abraflexi-bricks) | ^1.4 | Customer and document handling classes |
| [**HTML2PDF**](https://github.com/spipu/html2pdf) | ^5.3 | PDF generation from HTML templates |
| [**TCPDF**](https://github.com/tecnickcom/TCPDF) | ^6.10 | Advanced PDF processing |
| [**Huawei API**](https://github.com/hsp-dev/huawei-api) | dev-master | Communication with Huawei modems |
| [**Czech DataBox**](https://github.com/dfridrich/CzechDataBox) | ^1.3 | Czech ISDS data box integration |

### System Requirements

- **PHP**: 8.2 or higher
- **Extensions**: curl, json, mbstring, xml, zip
- **Optional**: gnokii (for SMS via mobile phones)
- **AbraFlexi**: Compatible version with REST API access

## Troubleshooting

### Common Issues

#### MultiFlexi JSON Validation Errors
If you encounter validation errors during MultiFlexi deployment:
```
JSON does not validate Violation: [environment.*.type] Does not have a value in the enumeration
```

**Solution**: The JSON schema has been updated. Ensure you're using the latest version (1.7.3+) which includes the schema compliance fixes.

#### SMS Not Sending
- Check SMS_ENGINE configuration
- Verify modem connectivity (for gnokii/huaweiapi)
- Test API credentials (for axfone)
- Check logs for detailed error messages

#### Email Delivery Issues
- Verify SMTP configuration in AbraFlexi
- Check MAX_MAIL_SIZE if attachments are large
- Ensure REMIND_FROM has proper sender reputation
- Test with MUTE=true first

#### AbraFlexi Connection Problems
- Verify ABRAFLEXI_URL is accessible
- Check credentials and company database name
- Ensure AbraFlexi user has sufficient permissions
- Test connection with `abraflexi-show-debts` first

### Debug Mode

Enable debug mode for detailed logging:
```bash
export APP_DEBUG=true
export EASE_LOGGER=console
./bin/abraflexi-reminder
```

Mohlo by vás zajímat
--------------------

* https://github.com/VitexSoftware/php-abraflexi-matcher - Párovač faktur 
* https://github.com/VitexSoftware/AbraFlexi-Digest      - Pravidelný souhrn vašeho AbraFlexi

## Documentation

- **[API Documentation](docs/API.md)** - Developer guide and extension points
- **[Environment Variables](docs/environment-variables.md)** - Complete configuration reference
- **[Changelog](CHANGELOG.md)** - Version history and changes
- **[Update Script](docs/update-docs.sh)** - Documentation maintenance tool

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure:
- All tests pass (`composer test`)
- Code follows PSR-12 standards (`composer fix-cs`)
- Documentation is updated
- MultiFlexi JSON files validate against schema

## Acknowledgments / Poděkování

This project would not exist without the support of [Spoje.Net s.r.o.](http://spoje.net/)

![Spoje.Net](https://raw.githubusercontent.com/VitexSoftware/php-abraflexi-reminder/master/logo-spojenet.png "Spoje.Net s.r.o.")

HTML version of reminders and inclusion of other receivables was funded by [Medinet s.r.o.](http://medinetsro.cz/)

![Medinet](https://raw.githubusercontent.com/VitexSoftware/php-abraflexi-reminder/master/mendinet-logo.png "Medinet s.r.o.")

## MultiFlexi Integration

AbraFlexi Reminder is fully compatible with [MultiFlexi](https://multiflexi.eu) platform and includes four ready-to-run applications:

| Application | Description | UUID |
|-------------|-------------|------|
| **Reminder** | Main reminder sender for overdue invoices | `0fd52fdd-1c83-4346-b9f9-13e82bd5d6d0` |
| **Inventarize** | Send customers overview of their obligations | Available in MultiFlexi |
| **Clean Labels** | Remove reminder labels from paid customers | Available in MultiFlexi |
| **Show Debts** | Generate debts overview report | Available in MultiFlexi |

<img src="abraflexi-inventarize.svg?raw=true" width="100" height="100"><img src="abraflexi-reminder-clean-labels.svg?raw=true" width="100" height="100"><img src="abraflexi-reminder.svg?raw=true" width="100" height="100"><img src="abraflexi-show-debts.svg?raw=true" width="100" height="100">

### MultiFlexi Deployment

1. Install MultiFlexi platform
2. Add AbraFlexi Reminder from the application catalog
3. Configure environment variables through MultiFlexi interface
4. Schedule automatic execution

All applications are validated against MultiFlexi schema version 2.1.1 and support:
- Environment-based configuration
- Docker container deployment
- Automatic dependency management
- Integrated logging and monitoring

See the full list of ready-to-run applications within the MultiFlexi platform on the [application list page](https://www.multiflexi.eu/apps.php).

[![MultiFlexi App](https://github.com/VitexSoftware/MultiFlexi/blob/main/doc/multiflexi-app.svg)](https://www.multiflexi.eu/apps.php)

## Recent Updates (v1.7.3)

- **JSON Schema Compliance**: Fixed MultiFlexi application JSON validation issues
- **Type Safety**: Updated environment variable types to match schema requirements:
  - `boolean` → `bool` for APP_DEBUG
  - `number` → `integer` for SURRENDER_DAYS and OVERDUE_PATIENCE
- **DataBox Improvements**: Enhanced Czech Data Box (ISDS) message sending
- **PHP 8.2+ Support**: Full compatibility with modern PHP versions
- **Strict Type Declarations**: Improved code reliability and performance
