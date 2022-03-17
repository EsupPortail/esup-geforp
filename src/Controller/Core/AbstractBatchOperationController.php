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
    /**
     * @Route("/batchoperation/dump", name="sygefor_core.batch.dump")
     */
    public function dumpAction()
    {
        $operations = $this->get('sygefor_core.batch_registry')->getAll();
        $operations_infos = array();

        foreach ($operations as $operation) {
            $operations_infos[] = array('label' => $operation->getLabel(), 'id' => $operation->getId(), 'ids' => 1);
        }

        return $this->render('SygeforCoreBundle:BatchOperation:dump.html.twig', array(
            'operations' => $operations_infos,
        ));
    }

    /**
     * @Route("/batchoperation/{id}/execute", name="sygefor_core.batch_operation.execute", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     */
    public function executeAction(Request $request, $id)
    {
        $ids = $request->get('ids');
        $options = $request->get('options');

        try {
            $this->get('monolog.logger.batch_operation')->addDebug("BatchOperation $id; ids: ".(string) $ids.'; options: '.(string) $options);
        } catch (\Exception $e) {
            $this->get('monolog.logger.batch_operation')->addDebug("BatchOperation $id; Exception: ".$e->getMessage());
        }

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options, $assoc = true);
            if (is_array($decodeOptions)) { //if translation succeeded, the result is stored as options array
                $options = $decodeOptions;
            }
        }

        if (count($request->files) > 0) {
            $attachments = array();
            //files are stored in option list using form name as key
            foreach ($request->files as $key => $file) {
                $attachments[] = $file;
            }
            $options['attachment'] = $attachments;
        }

        //also need to decode id list
        $decodeIds = json_decode($ids, $assoc = true);
        if (is_string($decodeIds)) {
            $ids = $decodeIds;
        }

        $ids = explode(',', $ids);

        $batchOperation = $this->get('sygefor_core.batch_registry')->get($id);

        if (!$batchOperation) {
            throw new NotFoundHttpException('Operation not found : '.$id);
        }

        $options = is_array($options) ? $options : array();
        $batchOperation->setOptions($options);

        return $batchOperation->execute($ids, $options);
    }

    /**
     * @Route("/batchoperation/modalconfig/{service}", name="sygefor_core.batch_operation.modal_config", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     */
    public function modalConfigAction(Request $request, $service)
    {
        $options = $request->get('options');

        //we try to read option list as a JSON string (case of multipart form type)
        if (is_string($options)) {
            $decodeOptions = json_decode($options, $assoc = true);
            if (is_array($decodeOptions)) {
                $options = $decodeOptions;
            }
        }

        $batchOperation = $this->get('sygefor_core.batch_registry')->get($service);
        if (method_exists($batchOperation, 'getModalConfig')) {
            return $batchOperation->getModalConfig($options);
        }

        return array();
    }

    /**
     * sends file.
     *
     * @Route("/batchoperation/{service}/get/{file}/as/{filename}", name="sygefor_core.batch_operation.get_file", options={"expose"=true}, defaults={"_format" = "json", "filename"=null})
     * @Rest\View
     */
    public function fileDownloadAction(Request $request, $service, $file, $filename = null)
    {
        $pdf = ($request->get('pdf') === 'true') ? true : false;
        $batchOperation = $this->get('sygefor_core.batch_registry')->get($service);

        if (method_exists($batchOperation, 'sendFile')) {
            return $batchOperation->sendFile($file, $filename ? $filename : 'publipostage.odt', array('pdf' => $pdf));
        }

        return array();
    }
}
