<?php
namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseService
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function createResponse($data, int $status = 200, array $groups = []): JsonResponse
    {
        $jsonContent = json_encode($data);  // Utilisation de json_encode pour tester
        return new JsonResponse($jsonContent, $status, [], true);
    }

    public function createErrorResponse(string $message, int $status = 400): JsonResponse
    {
        return new JsonResponse(['error' => $message], $status);
    }
}

