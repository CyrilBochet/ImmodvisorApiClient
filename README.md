# Immodvisor API Client

Client PHP pour l'API [Immodvisor](https://www.immodvisor.com) : récupération des avis clients et des notes des sociétés.

## Installation

```bash
composer require cyril-bochet/immodvisor-api-client
```

Prérequis : PHP >= 8.1, extensions `curl`, `calendar`, `json`, `simplexml`.

## Usage

```php
use ImmodvisorApiClient\Immodvisor\Immodvisor;

$client = new Immodvisor();

// Derniers avis + note d'une société (fallback automatique sur la marque
// si la société n'a pas d'avis ou pas de note)
$result = $client->getLastReview('API-KEY', 'SALT-IN', 'SALT-OUT', $companyId, 4);
// $result = [
//     'reviews'      => [...],       // derniers avis (objets)
//     'fallbackUsed' => false,       // true si avis de la marque utilisés
//     'rating'       => 4.9,         // null si l'appel API a échoué
//     'error'        => null,        // message d'erreur si échec, null sinon
// ]

// Ville et note d'une société
$info = $client->getCompanyCityAndRating('API-KEY', 'SALT-IN', 'SALT-OUT', $companyId);
// ['rate' => 4.9, 'city' => 'Lyon', 'fallbackUsed' => false] — ou [] si échec

// Liste des sociétés liées à la clé API
$companies = $client->getCompanyList('API-KEY', 'SALT-IN', 'SALT-OUT');
// [id => ['name' => ..., 'city' => ..., 'siret' => ..., 'rating' => ...], ...]
```

Passer `null` comme identifiant de société à `getLastReview` retourne les avis de la marque (toutes agences confondues).

Chaque réponse de l'API est vérifiée (code HTTP, status, checksum sortant) avant d'être exploitée.

## Structure

- `src/Immodvisor.php` — wrapper haut niveau (ce package)
- `src/Api.php`, `src/Utils.php`, `src/Date.php`, `src/ImmodvisorConfig.php`, `src/iApi.php` — librairie officielle Immodvisor (© immodvisor)

## Écarts par rapport à la librairie officielle

La librairie officielle (v1.7.0, 2019) est reprise avec des patchs minimaux, tous marqués `PATCH AFR` dans le code :

- `Api.php` : vérification TLS inconditionnelle (`CURLOPT_SSL_VERIFYPEER`/`VERIFYHOST`, était conditionnée au protocole du referer appelant) ; timeouts curl (connexion 5 s, total 10 s) ; User-Agent honnête ; garde null dans `datasAddString()`.
- `Utils.php` : `isEmail()` via `filter_var` (la regex d'origine rejetait les TLD de plus de 3 caractères) ; gardes null dans `isInt()`/`isFloat()` (Deprecated PHP 8).

Si Immodvisor publie une version plus récente, reporter ces points avant de remplacer les fichiers.
