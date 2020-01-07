<?php

namespace IM\Fabric\Package\API\Error\Subscriber;

use ApiPlatform\Core\Exception\ExceptionInterface as ApiPlatformException;
use Exception;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoggingHandler extends AbstractExceptionSubscriber
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger, string $appEnv, array $exceptionToStatus)
    {
        parent::__construct($appEnv, $exceptionToStatus);

        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['logException', 10]];
    }

    public function logException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ApiPlatformException) {
            return;
        }

        $statusCode = $this->getStatusCode($exception);
        $prettyException = $this->toArray($exception, $statusCode, 'dev');

        $level = $statusCode >= Response::HTTP_INTERNAL_SERVER_ERROR ? LogLevel::CRITICAL : LogLevel::ERROR;

        $this->logger->log($level, 'Error ' . $statusCode . ': ' . $prettyException['message'], [
            'trace' => $prettyException['trace']
        ]);
    }
}
