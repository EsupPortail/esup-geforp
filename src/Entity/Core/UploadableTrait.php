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
    /**
     * @ORM\Column(name="file_path", type="string", nullable=false)
     *
     * @var string
     */
    protected $filepath;

    /**
     * @ORM\Column(name="file_name", type="string", nullable=false)
     *
     * @var string
     */
    protected $filename;

    /**
     * @var File
     */
    protected $file;

    /**
     * used to force file update when changing file.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    protected $uploaded;

    /**
     * @var
     */
    static protected $maxFileSize = 50000000;

    public function __clone()
    {
        $file = $this->getFile();
        if (!empty($file)) {
            $this->id = null;
            $fs = new Filesystem();
            $tmpFileName = sha1(uniqid(mt_rand(), true)) . '.' . $file->getFileInfo()->getExtension();
            $fs->copy($this->getTemplatesRootDir() . '/' . $this->filepath, $this->getTemplatesRootDir() . '/' . $tmpFileName);
            $this->setFile(new File($this->getTemplatesRootDir() . '/' . $tmpFileName), $this->getFilename());
        }
    }

    /**
     * @param string $filePath
     */
    public function setFilepath($filePath)
    {
        $this->filepath = $filePath;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        if ($this->filepath !== null) {
            $this->file = new File($this->getTemplatesRootDir() . '/' . $this->filepath);
        }

        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilepath()
    {
        return $this->filepath;
    }

    /**
     * @param string $fileName
     */
    public function setFilename($fileName)
    {
        $this->filename = $fileName;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param File $file
     * @param string $name
     */
    public function setFile(File $file = null, $name = null)
    {
        if (!empty($file)) {
            $this->uploaded = new \DateTime();
            $this->file = $file;
            if ($this->file instanceof UploadedFile) {
                $this->filepath = sha1(uniqid(mt_rand(), true)) . '.' . $this->file->guessClientExtension();
                $this->filename = $this->file->getClientOriginalName();
            }
            else {
                $this->filepath = $file->getFileInfo()->getFilename();
                $this->filename = ($name) ? $name : $file->getFileInfo()->getFilename();
            }
        }
    }

    /**
     * @ORM\PrePersist()
     */
    public function preUpload()
    {
        if (null !== $this->file && ($this->file instanceof UploadedFile)) {
            // nom unique du fichier.
            $this->filepath = sha1(uniqid(mt_rand(), true)) . '.' . $this->file->guessClientExtension();
            $this->filename = $this->file->getClientOriginalName();
        }
    }

    /**
     * @return \DateTime
     */
    public function getUploaded()
    {
        return $this->uploaded;
    }

    /**
     * @param \DateTime $uploaded
     */
    public function setUploaded($uploaded)
    {
        $this->uploaded = $uploaded;
    }

    /**
     * @param PreUpdateEventArgs $args
     * @ORM\PreUpdate()
     */
    public function preUpdateUpload(PreUpdateEventArgs $args)
    {
        //a new file is set : we delete the old one
        if ($args->hasChangedField('uploaded')) {//new uploaded file : old one is deleted
            unlink($this->getTemplatesRootDir() . '/' . $args->getOldValue('filePath'));
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }
        $this->file->move($this->getTemplatesRootDir(), $this->filepath);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            unlink($file);
        }
    }

    /**
     * @return null|string
     */
    public function getAbsolutePath()
    {
        return (null === $this->filepath) ? null : $this->getTemplatesRootDir() . '/' . $this->filepath;
    }

    /**
     * @return string
     */
    protected function getTemplatesRootDir()
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
     * @return Response
     */
    public function send()
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

    /**
     * @param ExecutionContextInterface $context
     */
    public function validateFileSize(ExecutionContextInterface $context)
    {
        if ($this->file->getSize() > self::$maxFileSize) {
//            $context->addViolationAt('file', 'La taille du fichier dépasse la limite autorisée', array(), null);
            $context->buildViolation('La taille du fichier dépasse la limite autorisée')
                ->atPath('file')
                ->addViolation();

        }
    }
}
