<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\API\Error\Subscriber\Tests;

use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Throwable;

trait MocksExceptionEvents
{
    private function buildMockEvent(Throwable $throwable): ExceptionEvent|MockInterface
    {
        $event = Mockery::namedMock(ExceptionEvent::class, RequestEvent::class);
        $event->expects('getThrowable')->andReturns($throwable);
        return $event;
    }
}
