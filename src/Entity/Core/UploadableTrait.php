<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 10/07/14
 * Time: 14:45.
 */
namespace App\Entity\Core;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Class UploadableTrait.
 *
 * @ORM\HasLifecycleCallbacks
 */
trait UploadableTrait
{
    #[ORM\Column(name: 'file_path', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $filepath = null;

    #[ORM\Column(name: 'file_name', type: \Doctrine\DBAL\Types\Types::STRING)]
    protected ?string $filename = null;

    /**
     * @var File
     */
    protected File $file;

    /**
     * used to force file update when changing file.
     *
     */
    #[ORM\Column(type: \Doctrine\DBAL\Types\Types::DATETIME_MUTABLE)]
    protected ?\DateTimeInterface $uploaded = null;

    /**
     * @var
     */
    static protected int $maxFileSize = 50_000_000;

    public function __clone()
    {
        $file = $this->getFile();
        if (!empty($file)) {
            $this->id = null;
            $filesystem = new Filesystem();
            $tmpFileName = sha1(uniqid(random_int(0, mt_getrandmax()), true)) . '.' . $file->getFileInfo()->getExtension();
            $filesystem->copy($this->getTemplatesRootDir() . '/' . $this->filepath, $this->getTemplatesRootDir() . '/' . $tmpFileName);
            $this->setFile(new File($this->getTemplatesRootDir() . '/' . $tmpFileName), $this->getFilename());
        }
    }

    /**
     * @param string $filePath
     */
    public function setFilepath(string $filePath): void
    {
        $this->filepath = $filePath;
    }

    /**
     * @return File
     */
    public function getFile(): File
    {
        if ($this->filepath !== null) {
            $this->file = new File($this->getTemplatesRootDir() . '/' . $this->filepath);
        }

        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilepath(): string
    {
        return $this->filepath;
    }

    /**
     * @param string $fileName
     */
    public function setFilename(string $fileName): void
    {
        $this->filename = $fileName;
    }

    /**
     * @return string
     */
    public function getFilename(): string
    {
        return $this->filename;
    }

    /**
     * @param string $name
     */
    public function setFile(File $file = null, $name = null): void
    {
        if ($file instanceof \Symfony\Component\HttpFoundation\File\File) {
            $this->uploaded = new \DateTime();
            $this->file = $file;
            if ($this->file instanceof UploadedFile) {
                $this->filepath = sha1(uniqid(random_int(0, mt_getrandmax()), true)) . '.' . $this->file->guessClientExtension();
                $this->filename = $this->file->getClientOriginalName();
            }
            else {
                $this->filepath = $file->getFileInfo()->getFilename();
                $this->filename = $name ?: $file->getFileInfo()->getFilename();
            }
        }
    }

    #[ORM\PrePersist]
    public function preUpload(): void
    {
        if (null !== $this->file && ($this->file instanceof UploadedFile)) {
            // nom unique du fichier.
            $this->filepath = sha1(uniqid(random_int(0, mt_getrandmax()), true)) . '.' . $this->file->guessClientExtension();
            $this->filename = $this->file->getClientOriginalName();
        }
    }

    /**
     * @return \DateTimeInterface
     */
    public function getUploaded()
    {
        return $this->uploaded;
    }

    /**
     * @param \DateTime $uploaded
     */
    public function setUploaded(\DateTime $uploaded): void
    {
        $this->uploaded = $uploaded;
    }

    #[ORM\PreUpdate]
    public function preUpdateUpload(PreUpdateEventArgs $preUpdateEventArgs): void
    {
        //a new file is set : we delete the old one
        if ($preUpdateEventArgs->hasChangedField('uploaded')) {//new uploaded file : old one is deleted
            unlink($this->getTemplatesRootDir() . '/' . $preUpdateEventArgs->getOldValue('filepath'));
        }
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function upload(): void
    {

        $this->file->move($this->getTemplatesRootDir(), $this->filepath);

        unset($this->file);
    }

    #[ORM\PostRemove]
    public function removeUpload(): void
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    public function getAbsolutePath(): ?string
    {
        return (null === $this->filepath) ? null : $this->getTemplatesRootDir() . '/' . $this->filepath;
    }

    protected function getTemplatesRootDir(): string
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__ . '/../../../var/Material';
    }

    /**
     * @return mixed
     */
    public static function getMaxFileSize()
    {
        return self::$maxFileSize;
    }

    /**
     * Returns a response to send to the client if file is requested.
     *
     */
    public function send(): \Symfony\Component\HttpFoundation\Response
    {
        $response = new Response();
        //return array();
        $fp = $this->getAbsolutePath();

        // Set headers
        $response->headers->set('Cache-Control', 'private');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->getFilename() . '";');
        $response->headers->set('Content-length', filesize($fp));

        $response->sendHeaders();
        $response->setContent(readfile($fp));

        return $response;
    }

    public function validateFileSize(ExecutionContextInterface $executionContext): void
    {
        if ($this->file->getSize() > self::$maxFileSize) {
//            $context->addViolationAt('file', 'La taille du fichier dépasse la limite autorisée', array(), null);
            $executionContext->buildViolation('La taille du fichier dépasse la limite autorisée')
                ->atPath('file')
                ->addViolation();

        }
    }
}
