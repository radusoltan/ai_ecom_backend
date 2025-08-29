<?php

declare(strict_types=1);

namespace App\Shared\Http;

use App\Shared\Http\ResponseEnvelope;
use ApiPlatform\State\Pagination\PaginatorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

final class EnvelopeResponseSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RequestMetaFactory $metaFactory,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire('%response_envelope.enabled%')]
        private bool $enabled,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onView', -255],
            KernelEvents::EXCEPTION => ['onException', 0],
        ];
    }

    public function onView(ViewEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if ('json' !== $request->getRequestFormat() || $request->attributes->get('skip_envelope')) {
            return;
        }

        $result = $event->getControllerResult();
        $status = 200;
        $headers = [];
        if ($result instanceof JsonResponse) {
            $status = $result->getStatusCode();
            $headers = $result->headers->all();
            $data = json_decode((string) $result->getContent(), true);
            if (isset($data['status'], $data['data'], $data['meta'], $data['errors'])) {
                return;
            }
            $result = $data;
        }

        $data = $result;
        if ($result instanceof PaginatorInterface) {
            $items = iterator_to_array($result);
            $data = $items;
            $pagination = [
                'cursor' => null,
                'has_more' => $result->getCurrentPage() < $result->getLastPage(),
                'total' => (int) $result->getTotalItems(),
            ];
            $request->attributes->set('pagination', $pagination);
        }

        $envelope = new ResponseEnvelope('success', $data, $this->metaFactory->fromRequest($request));
        $response = new JsonResponse($envelope, $status);
        foreach ($headers as $name => $values) {
            $response->headers->set($name, implode(', ', $values));
        }
        $event->setResponse($response);
    }

    public function onException(ExceptionEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }
        $request = $event->getRequest();
        if ('json' !== $request->getRequestFormat() || $request->attributes->get('skip_envelope')) {
            return;
        }

        $throwable = $event->getThrowable();
        $statusCode = $throwable instanceof HttpExceptionInterface ? $throwable->getStatusCode() : 500;

        $errors = [];
        if ($throwable instanceof HttpExceptionInterface) {
            $errors[] = [
                'code' => $statusCode,
                'message' => $throwable->getMessage(),
            ];
        } elseif ($throwable instanceof ValidationFailedException) {
            foreach ($throwable->getViolations() as $violation) {
                $errors[] = [
                    'code' => $violation->getCode(),
                    'message' => $violation->getMessage(),
                    'field' => $violation->getPropertyPath() ?: null,
                ];
            }
        } elseif ($throwable instanceof ConstraintViolationListInterface) {
            foreach ($throwable as $violation) {
                $errors[] = [
                    'code' => $violation->getCode(),
                    'message' => $violation->getMessage(),
                    'field' => $violation->getPropertyPath() ?: null,
                ];
            }
        } else {
            $errors[] = [
                'code' => $statusCode,
                'message' => $throwable->getMessage(),
            ];
        }

        $envelope = new ResponseEnvelope('error', null, $this->metaFactory->fromRequest($request), $errors);
        $event->setResponse(new JsonResponse($envelope, $statusCode));
    }
}
