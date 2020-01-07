# IM API Error Handler
_Custom error handling and logging for API Platform_

## Setup

### Composer
Add the following to composer.json

_composer.json_
```json
{
    "require": {
        "immediate/im-api-error-handler": "^<CURRENT MAJOR VERSION>",
        "symfony/monolog-bundle": "^3.5"
    }
}
```

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
            # if *one* log is error or higher, pass *all* to file_log
            action_level: error
            handler: app_error
            channels: ['app']

        # now passed *all* logs, but only if one log is error or higher
        app_error:
            level: debug
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.error.log"

        deprecations:
            channels: ["php"]
            level: info
            max_level: info
            path: "%kernel.logs_dir%/%kernel.environment%.deprecations.log"
            type: stream
```
3. Create a `monolog.yaml` file in `config/packages/prod` and add the following:
```yaml
monolog:
    handlers:
        cloudwatch:
            type: service
            id: cloudwatch_handler
```
4. Add the following to `config/services.yaml`:
```yaml
services:
    # IM API Error Handler
    IM\Fabric\Package\API\Error\Subscriber\ErrorDisplayHandler:
        arguments: ['%kernel.environment%', '%api_platform.exception_to_status%']

    IM\Fabric\Package\API\Error\Subscriber\LoggingHandler:
        arguments: ['@logger', '%kernel.environment%', '%api_platform.exception_to_status%']
        tags:
            - { name: monolog.logger, channel: app }

    # AWS CloudWatch Logs Handler
    cloudwatch_client:
        class: Aws\CloudWatchLogs\CloudWatchLogsClient
        arguments:
            - credentials: { key: '%env(string:AWS_ACCESS_KEY)%', secret: '%env(string:AWS_SECRET_KEY)%' }
              region: '%env(string:AWS_REGION)%'
              version: '2014-03-28'

    cloudwatch_handler:
        class: Maxbanton\Cwh\Handler\CloudWatch
        arguments:
            - '@cloudwatch_client'
            - 'application-logs'
            - '%env(SERVICE_NAME)%-%env(AWS_CLOUDWATCH_STREAM_NAME)%'
            - ~ # Infinite retention (null)
```
5. Add the following variables to your `.env` file:
```sh
# AWS
AWS_REGION='eu-west-1'
AWS_CLOUDWATCH_STREAM_NAME='local'
```

## Testing Cloudwatch Integration
1. Ensure that you have a user with the correct privileges in AWS (See [here](https://github.com/maxbanton/cwh#aws-iam-needed-permissions) for a list of required permissions)
2. Generate an AWS access key for the appropriate environment
3. Copy the key and secret into your `.env.local`
```sh
AWS_ACCESS_KEY=<USER-TOKEN>
AWS_SECRET_KEY=<USER-SECRET>
```
4. Set the application to prod mode
5. Trigger some sort of error
6. Go to AWS Cloudwatch and open the `application-logs` group (See [here](https://eu-west-1.console.aws.amazon.com/cloudwatch/home?region=eu-west-1#logStream:group=application-logs) for EU-West-1)
7. The log stream will be along the lines of `<application-name>-local`
