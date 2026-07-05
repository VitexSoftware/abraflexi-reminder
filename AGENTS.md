# AGENTS.md — abraflexi-reminder

## Co projekt dělá

Upomíná zákazníky za nezaplacené faktury. Spravuje štítky na zákaznících
v AbraFlexi adresáři a volitelně odpojuje zákazníky od služby přes label.

Balíček: `vitexsoftware/abraflexi-reminder`  
Klíčová třída: `AbraFlexi\Reminder\Upominac`  
Notifiery: `src/AbraFlexi/Reminder/Notifier/*.php` — načítány automaticky

## Pipeline (MultiFlexi RunTemplates)

| RT | Název | Skript | Kdy |
|----|-------|--------|-----|
| RT52 | Payment Simulator | `abraflexi-payment-simulator` | každou hodinu |
| RT54 | Matcher | `abraflexi-matcher` | každou hodinu |
| RT53 | Reminder | `abraflexi-reminder` | denně 6:00 |
| RT55 | Clear Labels | `abraflexi-reminder-clean-labels` | denně 7:00 |
| RT56 | Debts Overview | `abraflexi-debts-overview` | denně 8:00 |
| RT57 | Notify Customers | `abraflexi-notify-customers` | denně 9:00 |

## Štítky zákazníků — stavový stroj

Toto je **kontraktní rozhraní** sdílené s dalšími projekty. Neměň sémantiku
štítků bez koordinace s `abraflexi-webhook-acceptor` a `isp-tools`.

| Štítek | Kdy se nastaví | Kdy se maže | Kdo čte |
|--------|---------------|-------------|---------|
| `UPOMINKA1` | Reminder, 1. upomínka | Clear Labels (po zaplacení) | — |
| `UPOMINKA2` | Reminder, 2. upomínka | Clear Labels (po zaplacení) | — |
| `UPOMINKA3` | Reminder, 3. upomínka | Clear Labels (po zaplacení) | — |
| `NEPLATIC`  | Reminder, score ≥ 3 | Clear Labels (po zaplacení) | ByServiceToggle |
| `ODPOJENO`  | ByServiceToggle (RT57) | Clear Labels (po zaplacení) | isp-tools |

### Env proměnné pro ByServiceToggle
- `SERVICE_TOGGLE_ENABLED=true` — opt-in aktivace (default: false)
- `SERVICE_DISCONNECT_LABEL=ODPOJENO` — název štítku (default: ODPOJENO)

Pokud `SERVICE_TOGGLE_ENABLED=true`, RT55 (Clear Labels) maže i `ODPOJENO`.

## Klíčové technické detaily

### getEvidenceDebts() — pohledavka
Evidence `pohledavka` může mít záznamy s null `typDoklK`. `AbraFlexi\Relation`
hodí `TypeError` při `Relation::fromTypDokl(null)`. Proto:
- `typDokl(typDoklK,kod)` se přidává do `colsToGet` **jen pro `faktura-vydana`**
- `includes=/evidence/typDokl` se nastavuje **jen pro `faktura-vydana`**
- Před přístupem k `$invoiceData['typDokl']` vždy použij `isset()`

### Score zákazníka
`score` = počet týdnů po splatnosti (integer). Práh pro `NEPLATIC` a
`ByServiceToggle` je `score >= 3`.

### Notifier interface
```php
interface notifier {
    public function compile(int $score, Customer $customer, array $clientDebts): bool;
}
```
Každá třída v namespace `AbraFlexi\Reminder\Notifier\` je automaticky
instancována v `processNotifyModules()`. Není třeba registrace.

### MUTE mode
`MUTE=true` — reminder nepošle žádné emaily ani SMS, ale štítky nastaví.
Vždy nastav v CI a testovacím prostředí.

## Env proměnné

| Proměnná | Popis |
|----------|-------|
| `ABRAFLEXI_URL` | URL AbraFlexi serveru |
| `ABRAFLEXI_LOGIN` | Login |
| `ABRAFLEXI_PASSWORD` | Heslo |
| `ABRAFLEXI_COMPANY` | Kód firmy |
| `REMIND_FROM` | Odesílací e-mail upomínek |
| `MUTE` | `true` = bez odesílání, jen štítky |
| `OVERDUE_PATIENCE` | Dny tolerance po splatnosti (default: 0) |
| `REMINDER_SKIPDOCTYPE` | Čárkou oddělené typy dokladů k přeskočení |
| `SERVICE_TOGGLE_ENABLED` | `true` = aktivovat ByServiceToggle |
| `SERVICE_DISCONNECT_LABEL` | Štítek pro odpojení (default: `ODPOJENO`) |

## Testovací prostředí

- AbraFlexi: `https://flexibee-dev.spoje.net:5434`, firma `spoje_net_s_r_o_`
- Credentials: `admin:wiwobr=metCob5`
- MultiFlexi: `https://vyvojar.spoje.net/multiflexi/`
- **Nikdy nepoužívej produkční credentials z `.env`** proti test serveru

## Debian packaging

```bash
fakeroot debian/rules clean
dpkg-buildpackage -us -uc -b
# výsledek v ../abraflexi-reminder_X.Y.Z_all.deb
scp ../abraflexi-reminder_*.deb vyvojar.spoje.net:/tmp/
ssh vyvojar.spoje.net 'sudo dpkg -i /tmp/abraflexi-reminder_*.deb'
```

Verze je v `debian/changelog`. Po změně verze vždy aktualizuj i `CHANGELOG.md`.

## Kompatibilita s abraflexi-webhook-acceptor

Webhook acceptor (`VitexSoftware/abraflexi-webhook-acceptor`) zachytává změny
z AbraFlexi Changes API. Pokud přidáváš nový štítek nebo měníš sémantiku
existujícího, aktualizuj `AGENTS.md` v obou projektech současně.
