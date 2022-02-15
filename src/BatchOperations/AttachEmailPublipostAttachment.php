<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/8/17
 * Time: 12:47 PM.
 */

namespace CoreBundle\BatchOperations;

/**
 * Class AttachEmailPublipostAttachment.
 */
trait AttachEmailPublipostAttachment
{
    /**
     * @param $message
     * @param $publipostTemplates
     * @param array $publipostIdList
     */
    protected function attachPublipostAttachment(\Swift_Message $message, $publipostTemplates, $publipostIdList)
    {
        foreach ($publipostTemplates as $publipostTemplate) {
            // find specific publipost service suffix
            $entityType = $publipostTemplate->getEntity();
            $entityType = explode('\\', $entityType);
            $entityType = $entityType[count($entityType) - 1];
            $serviceSuffix = strtolower($entityType);

            // call publipost action and generate pdf
            $publipostService = $this->container->get('sygefor_core.batch.publipost.'.$serviceSuffix);
            $publipostOptions = array('template' => $publipostTemplate->getId());
            $file = $publipostService->execute($publipostIdList, $publipostOptions);
            $fileName = $file['fileUrl'];
            $fileName = $publipostService->getTempDir().$publipostService->toPdf($fileName);

            // attach pdf to mail
            if (file_exists($fileName)) {
                $publipostSwiftAttachment = new \Swift_Attachment(file_get_contents($fileName), $publipostTemplate->getName().'.pdf');
                $message->attach($publipostSwiftAttachment);
            }
        }
    }
}
