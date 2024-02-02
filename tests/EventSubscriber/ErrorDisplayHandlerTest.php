<?php

declare(strict_types=1);

namespace IM\Fabric\Bundle\ApiErrorHandlerBundle\Tests\EventSubscriber;

use ApiPlatform\Exception\RuntimeException as ApiPlatformRuntimeException;
use Exception;
use IM\Fabric\Bundle\ApiErrorHandlerBundle\EventSubscriber\ErrorDisplayHandler;
use InvalidArgumentException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class ErrorDisplayHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use MocksExceptionEvents;

    public function testGetSubscribedEventsReturnsCallbackNameForExceptionEvent(): void
    {
        $expected = [KernelEvents::EXCEPTION => ['displayException', -10]];

        $this->assertSame($expected, ErrorDisplayHandler::getSubscribedEvents());
    }

    public function testDisplayExceptionIgnoresApiPlatformExceptions(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', []);

        $event = Mockery::namedMock(ExceptionEvent::class, RequestEvent::class);
        $event->expects('getThrowable')->andReturns(Mockery::mock(ApiPlatformRuntimeException::class));

        $event->shouldNotReceive('setResponse');

        $subscriber->displayException($event);
    }

    public function testDisplayExceptionSetStatusCodeBasedOnExceptionToStatusConfig(): void
    {
        $exception = new InvalidArgumentException('bad request');
        $statusCode = 400;
        $subscriber = new ErrorDisplayHandler('dev', [get_class($exception) => $statusCode]);
        $event = $this->buildMockEvent($exception);

        $event->expects('setResponse')->with(
            Mockery::on(
                function ($response) use ($exception, $statusCode) {
                    return
                        $response instanceof JsonResponse &&
                        $response->headers->get('Content-Type') === 'application/problem+json' &&
                        $response->getStatusCode() === $statusCode;
                }
            )
        );

        $subscriber->displayException($event);
    }

    public function testDisplayExceptionSetResponseForHttpExceptionWithTraceOnDev(): void
    {
        $exception = new NotFoundHttpException('not found');
        $subscriber = new ErrorDisplayHandler('dev', []);
        $event = $this->buildMockEvent($exception);

        $event->expects('setResponse')->with(
            Mockery::on(
                function ($response) use ($exception) {
                    $content = json_decode($response->getContent(), true);
                    return
                        $response instanceof JsonResponse &&
                        $response->headers->get('Content-Type') === 'application/problem+json' &&
                        $response->getStatusCode() === Response::HTTP_NOT_FOUND &&
                        $content['message'] === $exception->getMessage() &&
                        $content['status'] === Response::HTTP_NOT_FOUND &&
                        array_key_exists('trace', $content);
                }
            )
        );

        $subscriber->displayException($event);
    }

    public function testDisplayExceptionSetResponseWithoutTraceOnProd(): void
    {
        $subscriber = new ErrorDisplayHandler('prod', []);
        $exception = new NotFoundHttpException('not found');
        $event = $this->buildMockEvent($exception);

        $event->expects('setResponse')->with(
            Mockery::on(
                function ($response) use ($exception) {
                    $content = json_decode($response->getContent(), true);
                    return
                        $response instanceof JsonResponse &&
                        $response->headers->get('Content-Type') === 'application/problem+json' &&
                        $response->getStatusCode() === Response::HTTP_NOT_FOUND &&
                        $content['message'] === $exception->getMessage() &&
                        $content['status'] === Response::HTTP_NOT_FOUND &&
                        !array_key_exists('trace', $content);
                }
            )
        );

        $subscriber->displayException($event);
    }

    public function testDisplayExceptionSetInternalServerErrorForUnknownException(): void
    {
        $subscriber = new ErrorDisplayHandler('dev', []);
        $exception = new Exception('unknown error');
        $event = $this->buildMockEvent($exception);

        $event->expects('setResponse')->with(
            Mockery::on(
                function ($response) use ($exception) {
                    $content = json_decode($response->getContent(), true);
                    return
                        $response instanceof JsonResponse &&
                        $response->headers->get('Content-Type') === 'application/problem+json' &&
                        $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR &&
                        $content['message'] === $exception->getMessage() &&
                        $content['status'] === Response::HTTP_INTERNAL_SERVER_ERROR &&
                        array_key_exists('trace', $content);
                }
            )
        );

        $subscriber->displayException($event);
    }
}
