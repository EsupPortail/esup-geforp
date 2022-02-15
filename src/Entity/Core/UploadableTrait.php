<?php

/**
 * Created by PhpStorm.
 * BaseUser: maxime
 * Date: 10/07/14
 * Time: 14:45.
 */

namespace App\Entity\Core;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\ExecutionContextInterface;

/**
 * Class UploadableTrait.
 *
 * @ORM\HasLifecycleCallbacks
 */
trait UploadableTrait
{
    /**
     * @ORM\Column(name="file_path", type="string", nullable=true)
     *
     * @var string
     */
    protected $filePath;

    /**
     * @ORM\Column(name="file_name", type="string", nullable=true)
     *
     * @var string
     */
    protected $fileName;

    /**
     * @var File
     */
    protected $file;

    /**
     * used to force file update when changing file.
     *
     * @var \DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $uploaded;

    public function __clone()
    {
        $file = $this->getFile();
        if (!empty($file)) {
            $this->id = null;
            $fs = new Filesystem();
            $tmpFileName = sha1(uniqid(mt_rand(), true)).'.'.$file->getFileInfo()->getExtension();
            $fs->copy($this->getTemplatesRootDir().'/'.$this->filePath, $this->getTemplatesRootDir().'/'.$tmpFileName);
            $this->setFile(new File($this->getTemplatesRootDir().'/'.$tmpFileName), $this->getFileName());
        }
    }

    /**
     * @param string $filePath
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * @return File
     */
    public function getFile()
    {
        if ($this->filePath !== null && file_exists($this->getTemplatesRootDir().'/'.$this->filePath)) {
            $this->file = new File($this->getTemplatesRootDir().'/'.$this->filePath);
        }

        return $this->file;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        $this->slugFileName();
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

	/**
	 * @return int
	 * @Serializer\VirtualProperty
	 */
	public function getFileSize()
	{
		return filesize($this->getTemplatesRootDir().'/'.$this->filePath);
	}

    /**
     * @param File   $file
     * @param string $name
     */
    public function setFile(File $file = null, $name = null)
    {
        if (!empty($file)) {
            $this->uploaded = new \DateTime();
            $this->file = $file;
            if ($this->file instanceof UploadedFile) {
                $this->filePath = sha1(uniqid(mt_rand(), true)).'.'.$this->file->guessClientExtension();
                $this->fileName = $this->file->getClientOriginalName();
                $this->slugFileName();
            } else {
                $this->filePath = $file->getFileInfo()->getFilename();
                $this->fileName = ($name) ? $name : $file->getFileInfo()->getFilename();
                $this->slugFileName();
            }
        }
    }

    /**
     * @ORM\PrePersist()
     */
    public function preUpload()
    {
        if (null !== $this->file && ($this->file instanceof UploadedFile)) {
            $this->filePath = sha1(uniqid(mt_rand(), true)).'.'.$this->file->guessClientExtension();
            $this->fileName = $this->file->getClientOriginalName();
            $this->slugFileName();
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
        if ($args->hasChangedField('uploaded')) {
            //new uploaded file : old one is deleted
            try {
                unlink($this->getTemplatesRootDir().'/'.$args->getOldValue('filePath'));
            } catch (\Exception $e) {
            }
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
        $this->file->move($this->getTemplatesRootDir(), $this->filePath);

        unset($this->file);
    }

    /**
     * @ORM\PreRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            try {
                unlink($file);
                $this->filePath = null;
                $this->fileName = null;
                $this->file = null;
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * @return null|string
     */
    public function getAbsolutePath()
    {
        return (null === $this->filePath) ? null : $this->getTemplatesRootDir().'/'.$this->filePath;
    }

    /**
     * @return int file max size
     *             Default max size is 2Mo
     */
    public static function getMaxFileSize()
    {
        return 2097152;
    }

    /**
     * @return array
     */
    public static function getAllowedMimeTypes()
    {
        return array();
    }

    /**
     * @return array
     */
    public static function getNotAllowedMimeTypeMessage()
    {
        return null;
    }

    /**
     * Return max unit size.
     *
     * @return string
     */
    public function getHumanMaxFileSize()
    {
        $maxSize = $this->getMaxFileSize();
        for ($i = 0; $i < 8 && $maxSize >= 1024; ++$i) {
            $maxSize = $maxSize / 1024;
        }

        if ($i > 0) {
            return preg_replace('/,00$/', '', number_format($maxSize, 2, ',', ''))
            .' '.substr('KMGTPEZY', $i - 1, 1).'o';
        } else {
            return $maxSize.' o';
        }
    }

    /**
     * @Assert\Callback()
     */
    public function validateFile(ExecutionContextInterface $context)
    {
        $errorMessage = null;

        if (!$this->file) {
            return;
        }

        // check error
        if ($this->file->getError()) {
            // @see http://php.net/manual/fr/features.file-upload.errors.php
            $errorMessage = 'Il y a eu un problème lors de l\'envoi du fichier (code '.$this->file->getError().' )';
        } else {
            // check file max size
            if ($this->file->getSize() > $this->getMaxFileSize()) {
                $errorMessage = 'La taille du fichier dépasse la limite autorisée ('.$this->getHumanMaxFileSize().')';
            }
            // check file allowed mime types
            if ($this->file && !empty($this->file) &&
                !empty($this->getAllowedMimeTypes()) && !in_array($this->file->getMimeType(), $this->getAllowedMimeTypes())) {
                $errorMessage = $this->getNotAllowedMimeTypeMessage();
            }
        }

        // assert error
        if ($errorMessage) {
            $context->buildViolation($errorMessage)->atPath('file')->addViolation();
            $this->resetFile();
        }
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
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$this->getFileName().'";');
        $response->headers->set('Content-length', filesize($fp));
        $response->sendHeaders();
        $response->setContent(readfile($fp));

        return $response;
    }

    /**
     * Slug file name for PDF generation
     * Because pdf file name is get from file system.
     */
    public function slugFileName()
    {
        $this->fileName = str_replace(' ', '_', $this->fileName);
        $this->fileName = str_replace('\'', '-', $this->fileName);
    }

    /**
     * @return string
     */
    protected function getTemplatesRootDir()
    {
        // le chemin absolu du répertoire où les documents uploadés doivent être sauvegardés
        return __DIR__.'/../../../../var/Files';
    }

    /**
     * Reset form file.
     */
    protected function resetFile()
    {
        $this->file = null;
        $this->fileName = null;
        $this->filePath = null;
    }
}
