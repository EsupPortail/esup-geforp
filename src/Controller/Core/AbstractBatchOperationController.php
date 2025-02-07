<?php

namespace App\Controller\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class BatchOperationController.
 */
abstract class AbstractBatchOperationController extends AbstractController
{
    #[Route(path: '/batchoperation/dump', name: 'sygefor_core.batch.dump')]
    public function dump(): \Symfony\Component\HttpFoundation\Response
    {
        $operations = $this->get('sygefor_core.batch_registry')->getAll();
        $operations_infos = [];

        foreach ($operations as $operation) {
            $operations_infos[] = ['label' => $operation->getLabel(), 'id' => $operation->getId(), 'ids' => 1];
        }

        return $this->render('SygeforCoreBundle:BatchOperation:dump.html.twig', ['operations' => $operations_infos]);
    }

    /**
     * @Rest\View
     */
    #[Route(path: '/batchoperation/{id}/execute', name: 'sygefor_core.batch_operation.execute', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function execute(Request $request, $id)
    {
        $ids = $request->get('ids');
        $options = $request->get('options');

        try {
            $this->get('monolog.logger.batch_operation')->addDebug(sprintf('BatchOperation %s; ids: ', $id).$ids.'; options: '.$options);
        } catch (\Exception $exception) {
            $this->get('monolog.logger.batch_operation')->addDebug(sprintf('BatchOperation %s; Exception: ', $id).$exception->getMessage());
        }

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options, $assoc = true, 512, JSON_THROW_ON_ERROR);
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

        //also need to decode id list
        $decodeIds = json_decode((string) $ids, $assoc = true, 512, JSON_THROW_ON_ERROR);
        if (is_string($decodeIds)) {
            $ids = $decodeIds;
        }

        $ids = explode(',', (string) $ids);

        $batchOperation = $this->get('sygefor_core.batch_registry')->get($id);

        if (!$batchOperation) {
            throw new NotFoundHttpException('Operation not found : '.$id);
        }

        $options = is_array($options) ? $options : [];
        $batchOperation->setOptions($options);

        return $batchOperation->execute($ids, $options);
    }

    /**
     * @Rest\View
     */
    #[Route(path: '/batchoperation/modalconfig/{service}', name: 'sygefor_core.batch_operation.modal_config', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function modalConfig(Request $request, $service)
    {
        $options = $request->get('options');

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options, $assoc = true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decodeOptions)) {
                $options = $decodeOptions;
            }
        }

        $batchOperation = $this->get('sygefor_core.batch_registry')->get($service);
        if (method_exists($batchOperation, 'getModalConfig')) {
            return $batchOperation->getModalConfig($options);
        }

        return [];
    }

    /**
     * sends file.
     *
     * @Rest\View
     */
    #[Route(path: '/batchoperation/{service}/get/{file}/as/{filename}', name: 'sygefor_core.batch_operation.get_file', options: ['expose' => true], defaults: ['_format' => 'json', 'filename' => null])]
    public function fileDownload(Request $request, $service, $file, $filename = null)
    {
        $pdf = $request->get('pdf') === 'true';
        $batchOperation = $this->get('sygefor_core.batch_registry')->get($service);

        if (method_exists($batchOperation, 'sendFile')) {
            return $batchOperation->sendFile($file, $filename ?: 'publipostage.odt', ['pdf' => $pdf]);
        }

        return [];
    }
}
