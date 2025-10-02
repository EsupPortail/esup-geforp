<?php

namespace App\BatchOperations;

use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Email;

/**
 * Trait AttachEmailPublipostAttachment.
 */
trait AttachEmailPublipostAttachment
{
    private ?ContainerInterface $container = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function attachPublipostAttachment(Email $email, $publipostTemplates, array $publipostIdList, array $uploadedFiles = []): void
    {
        $this->attachUploadedFiles($email, $uploadedFiles);
        foreach ($publipostTemplates as $publipostTemplate) {
            $entityType = (string) $publipostTemplate->getEntity();
            $entityType = strtolower((new \ReflectionClass($entityType))->getShortName());
            $serviceId = 'sygefor_core.batch.publipost.' . $entityType;

            if (!$this->container->has($serviceId)) {
                continue;
            }

            $publipostService = $this->container->get($serviceId);
            $publipostOptions = ['template' => $publipostTemplate->getId()];
            $file = $publipostService->execute($publipostIdList, $publipostOptions);

            if (empty($file['fileUrl'])) {
                // fichier non généré
                continue;
            }

            // Génère le PDF (chemin relatif)
            $relativePdfPath = $publipostService->toPdf($file['fileUrl']);
            if (!$relativePdfPath) {
                // PDF non généré
                continue;
            }

            // Assemble le chemin complet
            $tempDir = rtrim($publipostService->getTempDir(), '/\\') . DIRECTORY_SEPARATOR;
            $filePath = $tempDir . ltrim($relativePdfPath, '/\\');

            // Nom de fichier sécurisé
            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '_', $publipostTemplate->getName()) . '.pdf';

            // Attachement
            $email->attachFromPath($filePath, $safeName, 'application/pdf');

            // DEBUG TEMP : à commenter ou logger avec monolog
            // dump("Attaché : $filePath => $safeName");
        }
    }
    protected function attachUploadedFiles(Email $email, array $uploadedFiles): void
    {

        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile && $file->isValid()) {
                $email->attachFromPath($file->getPathname(), $file->getClientOriginalName(), $file->getMimeType());
            }
        }
    }

}
