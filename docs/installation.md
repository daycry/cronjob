# Installation

Use the package with composer:

```bash
composer require daycry/cronjob
```

## Configuration

Run the following command to publish the config file:

```bash
php spark cronjob:publish
```

This will copy a config file to your app namespace. Adjust it as needed. By default, the file will be present in `app/Config/CronJob.php`.

To create the required database tables:

```bash
php spark migrate -all
```
