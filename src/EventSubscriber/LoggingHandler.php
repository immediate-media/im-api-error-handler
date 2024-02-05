<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\ApiErrorHandlerBundle\EventSubscriber;

use ApiPlatform\Exception\ExceptionInterface as ApiPlatformException;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class LoggingHandler extends AbstractExceptionSubscriber
{
    public function __construct(
        private readonly LoggerInterface $logger,
        protected string $appEnv,
        protected array $exceptionToStatus
    ) {
        parent::__construct($appEnv, $exceptionToStatus);
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['logException', 0]];
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
