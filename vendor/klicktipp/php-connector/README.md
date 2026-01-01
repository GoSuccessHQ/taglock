# KlickTipp PHP Connector

Der KlickTipp PHP Connector ist eine einfache PHP-Schnittstellenklasse zur Nutzung der KlickTipp REST API.  
Er unterstützt drei Authentifizierungsarten:

- Session Login (Benutzername + Passwort)
- API Key (Listbuilding)
- Developer Key + Customer Key (Partner-Integrationen)

Diese Bibliothek eignet sich ideal für einfache Integrationen, Legacy-Systeme oder Serverumgebungen ohne zusätzliche HTTP-Client-Bibliotheken.

---

## Installation

Installiere das Paket via Composer:

```bash
composer require klicktipp/php-connector
```

Danach kannst Du die Datei wie gewohnt einbinden:

```php
require 'vendor/klicktipp/php-connector/klicktipp.api.inc';
```

---

## Schnellstart

### Login → Subscriber hinzufügen → Logout

```php
require 'klicktipp.api.inc';

$k = new KlicktippConnector();
$k->login('username', 'password');

$subscriber = $k->subscribe(
    'email@example.com',
    123,                  // listid (optional)
    456,                  // tagid (optional)
    ['fieldFirstName' => 'Max']
);

print_r($subscriber);

$k->logout();
```

---

## Verfügbare Funktionen (Überblick)

### 🔑 Authentifizierung
- `login($username, $password)`
- `logout()`

### 👤 Kontakte
- `subscribe($email, $listid = 0, $tagid = 0, $fields = [], $smsnumber = '')`
- `subscriber_update($subscriberid, $fields = [], $newemail = '', $newsmsnumber = '')`
- `subscriber_get($subscriberid)`
- `subscriber_index()`
- `subscriber_delete($subscriberid)`
- `unsubscribe($email)`
- `subscriber_search($email)`
- `subscriber_tagged($tagid)`

### 🏷️ Tags
- `tag_index()`
- `tag_get($id)`
- `tag_create($name, $text)`
- `tag_update($id, $name, $text)`
- `tag_delete($id)`
- `tag($email, $tagids)`
- `untag($email, $tagid)`

### 📋 Opt-In Prozesse
- `subscription_process_index()`
- `subscription_process_get($id)`
- `subscription_process_redirect($id, $email)`

### 🔑 API Key Funktionen
- `signin($apikey, $email, $fields = [], $smsnumber = '')`
- `signout($apikey, $email)`
- `signoff($apikey, $email)`

### 🤝 Developer Key + Customer Key
Verwende statt `KlicktippConnector`:

```php
$k = new KlicktippPartnerConnector($username, $developer_key, $customer_key);
```

---

## Fehlerbehandlung

```php
if (!$result) {
    echo $k->get_last_error();
}
```

---

## Anforderungen

- HTTPS-Unterstützung

---

## Lizenz

MIT License  
(c) KlickTipp Team

---

## Support

- Website: https://www.klicktipp.com
- Developer Guide: https://www.klicktipp.com/de/support/wissensdatenbank/schnittstellen-api/  