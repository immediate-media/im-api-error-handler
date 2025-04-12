# IM API Error Handler
_Custom error handling and logging for API Platform_

## Setup

While this bundle can technically be used with any implementations of the [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/), we _highly_ recommend using it alongside Symfony's Monolog Bundle.

To use this bundle with Monolog, follow these steps:

### Composer
1. Add the following to composer.json
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/immediate-media/im-api-error-handler.git"
        }
    ]
}


```
2. Run `$ composer require immediate/im-api-error-handler symfony/monolog-bundle`

### Config
3. By default when first installing the Monolog bundle a default set of config files will be added to the `config/package/<env>` folders. Delete them.
4. Create a `monolog.yaml` file in `config/packages` and add the following:
```yaml
monolog:
    handlers:
        console:
            type: console
            process_psr_3_messages: false
            channels: ["!event", "!doctrine"]

        filter_for_errors:
            type: fingers_crossed
            # if *one* log is error or higher, pass *all* to app_error
            action_level: error
            handler: app_error
            channels: ['app', 'php']

        # now passed *all* logs, but only if one log is error or higher
        app_error:
            level: debug
            type: stream
            path: 'php://stdout'
```

What this means:
- Logs are only returned if there is at least one log of a level >= to "error"
- Logs are sent to the standard output of the container. Locally, you can see them by running `docker-compose logs app`.

You can edit what channels logs go to by declaring the DI for the logging handler in your services.yaml
