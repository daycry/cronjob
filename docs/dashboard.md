# Dashboard

You can access the web interface to view the status of jobs using the following URL:

```
https://example.com/cronjob
```

You must configure a username and password in the `CronJob.php` config file:

```php
public string $username = 'admin';
public string $password = 'admin';
```

## Custom Views

Go to `app/Config/CronJob.php` -> `views['dashboard']` and put your view's path there, like: `Cronjob/dashboard` (located in `app/Views/Cronjob/dashboard.php`).

```php
public array $views = [
    'login'     => '\Daycry\CronJob\Views\login',
    'dashboard' => '\Daycry\CronJob\Views\dashboard',
    'layout'    => '\Daycry\CronJob\Views\layout',
    'logs'      => '\Daycry\CronJob\Views\logs',
];
```

![CronJob List](/docs/images/cronjob-list.jpg)
