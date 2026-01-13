# Uputstva za Audit Log Sistem

## Pregled

Audit log sistem automatski prati sve akcije korisnika u aplikaciji i čuva ih u `activity_log` tabeli. Sistem koristi **spatie/laravel-activitylog** paket.

---

## Šta se automatski loguje

### 1. **User Model (Korisnici)**
- **Akcije:** Create, Update, Delete
- **Praćena polja:** name, email, organ_id, is_super_admin
- **Log name:** `users`

### 2. **PodaciORadnomMestu Model (Radna mesta)**
- **Akcije:** Create, Update, Delete, Replicate (Dupliranje)
- **Praćena polja:** vrsta_organa, organ, naziv_radnog_mesta, tip_konkursa, broj_izvrsilaca, zvanje, mesto_rada, status_konkursa_na_dan_1, status_konkursa_na_dan_2, datum_prvog_kreiranja, datum_poslednje_izmene, izabrani_kandidat, drugoplasirani_kandidat, prvi_dan_na_radu, provera_pfk
- **Log name:** `podaci_o_radnom_mestu`

### 3. **Authentication Events (Autentifikacija)**
- **Uspešan login:** Email, guard, remember flag
- **Neuspešan login:** Email, guard, attempted_at
- **Logout:** Email, guard
- **2FA Verifikacija:** Email, verification_method
- **Log name:** `auth`

### 4. **IP Adresa**
Sve akcije automatski čuvaju IP adresu korisnika koji je izvršio akciju.

---

## Pristup Audit Log-u

1. Prijavite se kao **Super Admin** korisnik
2. U navigacionom meniju, na kraju pod grupom **"Admin Panel"**, kliknite na **"Audit Log"**
3. Videćete listu svih aktivnosti

### Kolone u tabeli:
- **ID** - Jedinstveni identifikator zapisa
- **Тип** - Kategorija aktivnosti (badge sa bojama)
  - 🔵 Аутентификација (auth)
  - 🟢 Корисници (users)
  - 🟡 Радна места (podaci_o_radnom_mestu)
- **Акција** - Opis šta je urađeno
- **Корисник** - Email korisnika koji je izvršio akciju
- **Табела** - Nad kojim modelom je radjena akcija
- **IP Адреса** - IP adresa sa koje je akcija izvršena
- **Датум и време** - Kada je akcija izvršena (format: dd/mm/yyyy HH:mm:ss)

### Pretraga i Filtriranje:
- **Pretraga:** Po ID, tipu, akciji, korisniku, IP adresi, datumu
- **Filteri:**
  - Po tipu aktivnosti (može se izabrati više)
  - Po korisniku (searchable dropdown)
  - Po datumskom opsegu (od/do)

### Pregled Detalja:
Kliknite na **"Преглед"** dugme da vidite detaljne informacije:
- Opšte informacije
- Subject Information (model nad kojim je radjena akcija)
- **Старе вредности** - Vrednosti pre izmene
- **Нове вредности** - Vrednosti posle izmene
- Dodatne informacije (za auth events)

---

## Automatsko Brisanje Starih Logova

### Kako radi?

**Scheduled Task** se pokreće **svaki dan** i automatski briše stare audit logove.

**Lokacija:** `routes/console.php` (linija 12)
```php
Schedule::command('activitylog:clean')->daily();
```

### Retention Period (Период чувања)

**Trenutno podešavanje:** Logovi se čuvaju **365 dana (1 godina)**, posle čega se automatski brišu.

**Lokacija:** `config/activitylog.php` (linija 14)
```php
'delete_records_older_than_days' => 365,
```

### Promena Retention Period-a

Da promenite koliko dugo se logovi čuvaju, izmenite vrednost u `config/activitylog.php`:

```php
'delete_records_older_than_days' => 90,  // 3 meseca
'delete_records_older_than_days' => 180, // 6 meseci
'delete_records_older_than_days' => 365, // 1 godina (trenutno)
'delete_records_older_than_days' => 730, // 2 godine
```

**Napomena:** Posle izmene, pokrenite:
```bash
php artisan config:cache
```

---

## Ručno Pokretanje Cleanup-a

Možete ručno pokrenuti brisanje starih logova:

```bash
# Obriši logove starije od 365 dana (default iz config)
php artisan activitylog:clean

# Obriši logove starije od 30 dana (custom)
php artisan activitylog:clean --days=30

# Obriši samo određeni tip logova (npr. auth)
php artisan activitylog:clean auth --days=90
```

---

## Provera Broja Logova

Da proverite koliko logova je trenutno u bazi:

```bash
php artisan tinker --execute="echo \Spatie\Activitylog\Models\Activity::count() . ' ukupno logova';"
```

Ili grupisano po tipu:

```bash
php artisan tinker --execute="\Spatie\Activitylog\Models\Activity::selectRaw('log_name, count(*) as count')->groupBy('log_name')->get()->each(fn(\$row) => print(\$row->log_name . ': ' . \$row->count . PHP_EOL));"
```

---

## ⚠️ VAŽNO: Laravel Scheduler Setup

Da bi automatsko brisanje radilo, **Laravel Scheduler mora biti pokrenut na serveru**.

### Production Server Setup

Dodajte sledeći **cron job** na serveru (kao root ili deployment user):

```bash
crontab -e
```

Dodajte liniju:
```
* * * * * cd /path/to/KPRM && php artisan schedule:run >> /dev/null 2>&1
```

**Zamenite** `/path/to/KPRM` sa stvarnom putanjom do projekta.

Ovaj cron job se pokreće **svaki minut** i proverava da li ima schedulovanih taskova koji treba da se izvrše.

### Provera da li Scheduler radi

```bash
# Lista svih schedulovanih taskova
php artisan schedule:list

# Pokreni scheduler ručno (za testiranje)
php artisan schedule:run
```

---

## JSON Format Properties

Stare i nove vrednosti se čuvaju u JSON formatu u `properties` koloni:

### Primer za Update:
```json
{
  "attributes": {
    "name": "Novo Ime",
    "email": "novo@email.com"
  },
  "old": {
    "name": "Staro Ime",
    "email": "staro@email.com"
  }
}
```

### Primer za Login:
```json
{
  "email": "user@example.com",
  "guard": "web",
  "remember": false
}
```

### Primer za Failed Login:
```json
{
  "email": "attempted@email.com",
  "guard": "web",
  "attempted_at": "2026-01-13 15:30:45"
}
```

---

## Struktura Baze Podataka

### Tabela: `activity_log`

| Kolona | Tip | Opis |
|--------|-----|------|
| `id` | bigint | Primarni ključ |
| `log_name` | string | Kategorija: 'auth', 'users', 'podaci_o_radnom_mestu' |
| `description` | text | Opis akcije |
| `subject_type` | string | Model: 'App\Models\User', 'App\Models\PodaciORadnomMestu' |
| `subject_id` | bigint | ID zapisa nad kojim je radjena akcija |
| `causer_type` | string | Ko je izvršio: 'App\Models\User' |
| `causer_id` | bigint | ID korisnika koji je izvršio |
| `properties` | json | Old/New vrednosti + dodatni podaci |
| `batch_uuid` | uuid | UUID batcha (ako je korišćen) |
| `event` | string | Event type |
| `ip_address` | string(45) | **CUSTOM KOLONA** - IP adresa |
| `created_at` | timestamp | Datum kreiranja |
| `updated_at` | timestamp | Datum izmene |

**Indices:**
- `log_name` - Za brže pretraživanje po tipu

---

## Sigurnosne Napomene

### Šta SE LOGUJE:
- ✅ Sve promene nad korisnicima (osim passworda)
- ✅ Sve promene nad radnim mestima
- ✅ Svi authentication eventi
- ✅ IP adrese

### Šta SE NE LOGUJE:
- ❌ **Password** - Iz sigurnosnih razloga
- ❌ **two_factor_code** - Iz sigurnosnih razloga
- ❌ **Settings** - Već ima svoju istoriju
- ❌ **Roles** - Retko se menjaju (može se dodati po potrebi)
- ❌ **READ operacije** - Samo Create, Update, Delete

### Pristup:
- ❌ Samo **Super Admin** korisnici mogu pristupiti Audit Log-u
- ❌ **Read-only** - Ne mogu se menjati ili brisati logovi ručno kroz UI
- ✅ Logovi se mogu brisati samo kroz scheduled cleanup ili ručno preko komande

---

## Dodavanje Novih Modela Za Praćenje

Ako želite da dodate praćenje za neki novi model:

1. Dodajte trait i import na vrh modela:
```php
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class NoviModel extends Model
{
    use LogsActivity;

    // ... postojeći kod ...
}
```

2. Dodajte konfiguraciju:
```php
public function getActivitylogOptions(): LogOptions
{
    return LogOptions::defaults()
        ->logOnly(['polje1', 'polje2', 'polje3']) // Polja koja želite da pratite
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs()
        ->setDescriptionForEvent(fn(string $eventName) => match($eventName) {
            'created' => 'Kreiran novi zapis',
            'updated' => 'Ažuriran zapis',
            'deleted' => 'Obrisan zapis',
            default => "Zapis {$eventName}",
        })
        ->useLogName('novi_model'); // Jedinstveno ime za ovaj tip
}

public function tapActivity(\Spatie\Activitylog\Contracts\Activity $activity, string $eventName)
{
    $activity->ip_address = request()->ip();
}
```

---

## Troubleshooting

### Problem: Logovi se ne kreiraju

**Rešenje:**
1. Proverite da li je `LogsActivity` trait dodat na model
2. Proverite `getActivitylogOptions()` metodu
3. Proverite da li je activitylog enabled u config: `config/activitylog.php` linija 8
4. Clear cache: `php artisan config:cache`

### Problem: IP adresa je null

**Rešenje:**
1. Proverite da li je `tapActivity()` metoda dodata na model
2. Proverite da li je `ip_address` kolona u bazi (migracija)

### Problem: Login/Logout eventi se ne loguju

**Rešenje:**
1. Proverite da li je `EventServiceProvider` registrovan u `bootstrap/providers.php`
2. Clear cache: `php artisan optimize:clear`
3. Proverite `app/Listeners/LogAuthenticationEvents.php`

### Problem: Automatsko brisanje ne radi

**Rešenje:**
1. Proverite cron job na serveru
2. Pokrenite ručno: `php artisan schedule:run`
3. Proverite schedulovane taskove: `php artisan schedule:list`

---

## Kontakt za Podršku

Za dodatna pitanja ili probleme sa audit log sistemom, kontaktirajte sistem administratora ili developera.

**Datum kreiranja:** 13.01.2026
**Verzija:** 1.0
**Paket:** spatie/laravel-activitylog v4.10.2
