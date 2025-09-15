<?php

namespace App\Controller\Core;

use App\BatchOperations\BatchOperationRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Class BatchOperationController.
 */
final class BatchOperationController extends AbstractController
{
    private string $defaultTemplate;

    public function __construct(ParameterBagInterface $params, private readonly NormalizerInterface $normalizer)
    {
        $this->defaultTemplate = $params->get('app.default_pdf_template');
    }
    /**
     * @return array{operations: array<int, array{label: mixed, id: mixed, ids: int}>}
     */
    #[Route(path: '/batchoperation/dump', name: 'sygefor_core.batch.dump')]
    public function dump(BatchOperationRegistry $batchOperationRegistry): JsonResponse
    {
        $operations = $batchOperationRegistry->getAll();
        $operations_infos = [];

        foreach ($operations as $operation) {
            $operations_infos[] = [
                'label' => $operation->getLabel(),
                'id'    => $operation->getId(),
                'ids'   => 1
            ];
        }

        return new JsonResponse(['operations' => $operations_infos]);
    }

    /**
     * @Rest\View
     * @throws \JsonException
     */
    #[Rest\View()]
    #[Route(
        path: '/batchoperation/{id}/execute',
        name: 'sygefor_core.batch_operation.execute',
        options: ['expose' => true],
        defaults: ['_format' => 'json']
    )]
    public function execute(string $id, BatchOperationRegistry $batchOperationRegistry, Request $request)
    {
        $contentType = $request->headers->get('Content-Type');
        $isJson = str_contains($contentType, 'application/json');

        // Récupération des données selon le type de requête
        if ($isJson) {
            $data = json_decode($request->getContent(), true);
            $idsRaw = $data['ids'] ?? [];
            $options = $data['options'] ?? [];
        } else {
            $idsRaw = $request->get('ids');
            $options = $request->get('options');
        }

        // Gestion des options JSON string (cas multipart/form)
        if (is_string($options)) {
            $decodedOptions = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decodedOptions)) {
                $options = $decodedOptions;
            }
        }
        $options = is_array($options) ? $options : [];

        // Gestion des fichiers uploadés
        if (count($request->files) > 0) {
            $attachments = [];
            foreach ($request->files as $file) {
                $attachments[] = $file;
            }
            $options['attachment'] = $attachments;
        }


// Gestion robuste des IDs
        if (is_array($idsRaw)) {
            $ids = $idsRaw;
        } else {
            $idsString = (string) $idsRaw;

            // Essaye de décoder en JSON
            try {
                $decodedIds = json_decode($idsString, true, 512, JSON_THROW_ON_ERROR);

                if (is_array($decodedIds)) {
                    $ids = $decodedIds;
                } elseif (is_string($decodedIds)) {
                    $ids = explode(',', $decodedIds);
                } else {
                    $ids = explode(',', $idsString);
                }
            } catch (\JsonException) {
                // Si ce n’est pas un JSON valide, traite comme CSV "1,2,3"
                $ids = explode(',', $idsString);
            }
        }

// Nettoyage final : suppression de tous les guillemets et conversion en int
        $ids = array_map(function($id) {
            // Supprime récursivement tous les guillemets de début/fin
            while (is_string($id) && preg_match('/^"+(.+?)"+$/', $id, $matches)) {
                $id = $matches[1];
            }
            return (int) $id;
        }, $ids);

        // Récupération de l'opération batch
        $batchOperation = $batchOperationRegistry->getByName($id);
        if (!$batchOperation) {
            throw new NotFoundHttpException('Operation not found: ' . $id);
        }

        $batchOperation->setOptions($options);
        //dump($options);
        // Exécution de l'opération avec les IDs nettoyés
        return $batchOperation->execute($ids, $options);
    }


    /**
     * @Rest\View
     */
    #[Route(path: '/batchoperation/modalconfig/{service}', name: 'sygefor_core.batch_operation.modal_config', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function modalConfig($service, BatchOperationRegistry $batchOperationRegistry, Request $request): array
    {
        $options = $request->get('options');

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decodeOptions)) { //if translation succeeded, the result is stored as options array
                $options = $decodeOptions;
            }
        }

        //$batchOperation = $this->get('sygefor_core.batch_operation_registry')->get($service);
        $batchOperation = $batchOperationRegistry->getByName($service);

        if (!$batchOperation) {
            throw new NotFoundHttpException("Batch operation service '$service' not found.");
        }


        if (method_exists($batchOperation, 'getModalConfig')) {
            return $batchOperation->getModalConfig($options);
        }

        return [];
    }

    private function normalize($data)
    {
        if ($data instanceof \JsonSerializable) {
            return $data->jsonSerialize();
        }

        if (is_object($data)) {
            return method_exists($data, 'toArray') ? $data->toArray() : get_object_vars($data);
        }

        return $data;
    }

    /**
     * sends file.
     *
     * @Rest\View
     */
    #[Rest\View()]
    #[Route(path: '/batchoperation/{service}/get/{file}/as/{filename}', name: 'sygefor_core.batch_operation.get_file', options: ['expose' => true], defaults: ['_format' => 'json', 'filename' => null])]
    public function fileDownload(Request $request, $service, BatchOperationRegistry $batchOperationRegistry, $file, $filename = null)
    {
        $pdf = $request->get('pdf') === 'true';
        //$batchOperation = $this->get('sygefor_core.batch_operation_registry')->get($service);
        $batchOperation = $batchOperationRegistry->getByName($service);

        if (method_exists($batchOperation, 'sendFile')) {
            return $batchOperation->sendFile($file, $filename ?: 'publipostage.odt', ['pdf' => $pdf]);
        }

        return [];
    }
}
