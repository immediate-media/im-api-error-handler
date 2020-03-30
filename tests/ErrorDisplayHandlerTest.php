<?php

namespace IM\Fabric\Package\API\Error\Subscriber\Tests;

use ApiPlatform\Core\Exception\RuntimeException as ApiPlatformRuntimeException;
use IM\Fabric\Package\API\Error\Subscriber\ErrorDisplayHandler;
use Exception;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Throwable;

class ErrorDisplayHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testGetSubscribedEventsReturnsCallbackNameForExceptionEvent(): void
    {
        $expected = [KernelEvents::EXCEPTION => ['displayException', -10]];

        $this->assertSame($expected, ErrorDisplayHandler::getSubscribedEvents());
    }

    public function testDisplayExceptionIgnoresApiPlatformExceptions(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', []);

        $event = $this->buildMockEvent(Mockery::mock(ApiPlatformRuntimeException::class));

        $event->shouldNotReceive('setResponse');

        $subscriber->displayException($event);
    }

    public function testDisplayExceptionSetStatusCodeBasedOnExceptionToStatusConfig(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', [InvalidArgumentException::class => 400]);
        $event = $this->buildMockEvent(new InvalidArgumentException('bad request'));

        $subscriber->displayException($event);

        $this->assertSame(400, $event->getResponse()->getStatusCode());
    }

    public function testDisplayExceptionSetResponseForHttpExceptionWithTraceOnDev(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', []);
        $event = $this->buildMockEvent(new NotFoundHttpException('not found'));

        $subscriber->displayException($event);

        $response = $event->getResponse();
        $body = json_decode($event->getResponse()->getContent(), true);

        $this->assertInstanceOf(JsonResponse::class, $event->getResponse());
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('not found', $body['message']);
        $this->assertSame(404, $body['status']);
        $this->assertArrayHasKey('trace', $body);
    }

    public function testDisplayExceptionSetResponseWithoutTraceOnProd(): void
    {
        $subscriber = new ErrorDisplayHandler('prod', []);
        $event = $this->buildMockEvent(new NotFoundHttpException('not found'));

        $subscriber->displayException($event);

        $body = json_decode($event->getResponse()->getContent(), true);

        $this->assertSame(404, $event->getResponse()->getStatusCode());
        $this->assertArrayNotHasKey('trace', $body);
    }

    public function testDisplayExceptionSet500ForUnknownException(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', []);
        $event = $this->buildMockEvent(new Exception('unknown error'));

        $subscriber->displayException($event);

        $this->assertSame(500, $event->getResponse()->getStatusCode());
    }

    /**
     * @param Throwable $throwable
     * @return ExceptionEvent|MockInterface
     */
    private function buildMockEvent(Throwable $throwable): ExceptionEvent
    {
        $event = Mockery::mock(ExceptionEvent::class)->makePartial();
        $event->shouldReceive('getThrowable')->andReturn($throwable);

        return $event;
    }
}
