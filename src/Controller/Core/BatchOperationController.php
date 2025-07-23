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
    public function dump(): array
    {
        $operations = $this->get('sygefor_core.batch_operation_registry')->getAll();
        $operations_infos = [];

        foreach ($operations as $operation) {
            $operations_infos [] = ['label' => $operation->getLabel(), 'id' => $operation->getId(), 'ids' => 1];
        }

        return ['operations' => $operations_infos];
    }

    /**
     * @Rest\View
     * @throws \JsonException
     */
    #[Rest\View()]
    #[Route(path: '/batchoperation/{id}/execute', name: 'sygefor_core.batch_operation.execute', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function execute(string $id, BatchOperationRegistry $batchOperationRegistry, Request $request)
    {
        $idsRaw = $request->get('ids');
        $options = $request->get('options');

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options,true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decodeOptions)) { //if translation succeeded, the result is stored as options array
                $options = $decodeOptions;
            }
        }

        if (count($request->files) > 0) {
            $attachments = [];
            //files are stored in option list using form name as key
            foreach ($request->files as $file) {
                $attachments[] = $file;
            }

            $options['attachment'] = $attachments;
        }

        if (is_array($idsRaw)) {
            $ids = $idsRaw;
        } else {
            // Sinon on tente de le parser
            $idsString = (string) $idsRaw;

            try {
                $decodedIds = json_decode($idsString, true, 512, JSON_THROW_ON_ERROR);

                // Si json_decode donne un tableau
                if (is_array($decodedIds)) {
                    $ids = $decodedIds;
                }
                // Si ça donne une string "1,2,3", on explose
                elseif (is_string($decodedIds)) {
                    $ids = explode(',', $decodedIds);
                }
                // Autres cas : on essaie en CSV direct
                else {
                    $ids = explode(',', $idsString);
                }
            } catch (\JsonException) {
                // Si pas un JSON valide, on traite comme CSV "1,2,3"
                $ids = explode(',', $idsString);
            }
        }

        //$batchOperation = $this->get('sygefor_core.batch_operation_registry')->get($id);
        $batchOperation = $batchOperationRegistry->getByName($id);


        if (!$batchOperation) {
            throw new NotFoundHttpException('Operation not found: ' . $id);
        }

        $options = is_array($options) ? $options : [];
        $batchOperation->setOptions($options);

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
