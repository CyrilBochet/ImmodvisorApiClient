This composer package allows you to get all immodvisor client feedbacks

Installation 
  
```php
composer require cyril-bochet/immodvisor-api-client
```

Usage

```php

use ImmodvisorApiClient\Immodvisor\Immodvisor;


$clientFeedback = new Immodvisor();

$last_review =  $clientFeedback->lastReviews('API-Key','SALTIN','SALTOUT','COMPANY ID or null to get all company branches feedback','number of feedback');

$header =  $clientFeedback->headerReviews('API-Key','SALTIN','SALTOUT','COMPANY ID');






```
