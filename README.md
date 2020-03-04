# Laravel Error Tracker
This is a package that catches errors when it occurs and sends a POST request with all required information to a specified endpoint. This is mainly for production usages where if an error occurs for other people you'll have the same information as you would be debugging with the use of ignition locally.  
  
This package is meant to be used with https://github.com/PollieDev/laravel-error-tracker-app but you are free to create your own app if you'd like with the same endpoint as the app. (Which would be {URL}/api/report, URL is provided in config).

## Installation
Just install the package through composer:
```bash
composer require polliedev/laravel-error-tracker
```
It'll auto register.  
  
You will need to specify the URL of your tracker app in the config. So first publish the config:
```bash
php artisan vendor:publish --provider="PollieDev\LaravelErrorTracker\LaravelErrorTrackerServiceProvider" --tag=config
```

After that change the base_url in config to the URL of your app (ex: https://mytrackerapp.com).  
Do not append the `/api/report` (which is the endpoint) to the URL.

## Options
Next to the base_url you'll also find a `meta_data` option in the config.
This is additional data that you'd like to send to the tracker app. It should be an associative array with values that are either a value that can be json decoded or a function (if you need more complex things to be parsed) that returns a value that can be decoded.  
This could be the version of your CMS or any specific information.
