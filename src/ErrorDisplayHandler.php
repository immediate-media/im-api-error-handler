<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\API\Error\Subscriber;

use ApiPlatform\Exception\ExceptionInterface as ApiPlatformException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;

class ErrorDisplayHandler extends AbstractExceptionSubscriber
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => ['displayException', -10]];
    }

    public function displayException(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof ApiPlatformException) {
            return;
        }

        $statusCode = $this->getStatusCode($throwable);

        $response = new JsonResponse($this->toArray($throwable, $statusCode), $statusCode);
        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }
}
