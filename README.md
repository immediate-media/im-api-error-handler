# IM API Error Handler
_Custom error handling and logging for API Platform_

## Setup

While this bundle can technically be used with any implementations of the [PSR-3 Logger Interface](https://www.php-fig.org/psr/psr-3/),
we _highly_ recommend using it alongside Symfony's Monolog Bundle.

To use this package with Monolog, follow these steps:

### Composer
1. Add the following to composer.json
```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.immediate.co.uk/WCP-Packages/im-api-error-handler.git"
        }
    ]
}
```
2. Run `$ composer require immediate/im-api-error-handler symfony/monolog-bundle`

### Config
1. By default when first installing the Monolog bundle a default set of config files will be added to the `config/package/<env>` folders. Delete them.
2. Create a `monolog.yaml` file in `config/packages` and add the following:
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

3. Add the following to `config/services.yaml`:
```yaml
services:
    IM\Fabric\Bundle\API\Error\Subscriber\LoggingHandler:
        arguments: ['@logger', '%kernel.environment%', '%api_platform.exception_to_status%']
        tags:
            - { name: monolog.logger, channel: app }
```

### Upgrading from existing installation of im-api-error-handler package in an API

4. Remove existing package before installing this bundle. 

```json
    composer remove immediate/im-api-error-handler
```

5. Check the API's 'config/services.yaml' for the content below

```yaml
   # IM API Error Handler
   IM\Fabric\Bundle\API\Error\Subscriber\ErrorDisplayHandler:
   arguments: [ '%kernel.environment%', '%api_platform.exception_to_status%' ]

   IM\Fabric\Bundle\API\Error\Subscriber\LoggingHandler:
   arguments: [ '@logger', '%kernel.environment%', '%api_platform.exception_to_status%' ]
   tags:
   - { name: monolog.logger, channel: app }
```
6. Remove previous configuration
    - Remove the first block referring to the 'ErrorDisplayHandler'
    - If you require 'monolog' then leave the second block but if not you can remove that also


7. Install im-api-handler bundle

```json
    composer config repositories.repo-name vcs https://github.immediate.co.uk/WCP-Packages/im-api-error-handler.git
    composer require immediate/im-api-error-handler
```
8. Check the API's 'config/bundles.php' for the content below

```php

    IM\Fabric\Bundle\API\Error\Subscriber\ApiErrorHandlerBundle::class => ['all' => true],

```
9. If it is not present then add it



