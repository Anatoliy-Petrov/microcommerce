<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        protected readonly ValidatorInterface $validator,
    ) {}

    protected function validateRequest(object $dto): ?JsonResponse
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) === 0) {
            return null;
        }

        $errors = [];
        foreach ($violations as $violation) {
            $errors[] = [
                'field'   => $violation->getPropertyPath(),
                'message' => $violation->getMessage(),
            ];
        }

        return $this->error($errors, Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    protected function success(mixed $data, int $status = Response::HTTP_OK): JsonResponse
    {
        return $this->json(['data' => $data, 'meta' => new \stdClass(), 'errors' => []], $status);
    }

    protected function error(array $errors, int $status): JsonResponse
    {
        return $this->json(['data' => null, 'meta' => new \stdClass(), 'errors' => $errors], $status);
    }
}