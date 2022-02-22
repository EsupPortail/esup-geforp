<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 16:56.
 */

namespace App\BatchOperations\Generic;

use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\SecurityContext;
use Volcanus\Csv\Writer;

/**
 * Class CSVBatchOperation.
 */
class CSVBatchOperation extends AbstractBatchOperation
{
    /**
     * @var EntityManager
     */
    protected $securityContext;

    /**
     * @var array
     */
    protected $options = array(
        'volcanus_config' => array(
            'delimiter' => ';',
            'enclose' => true,
            'enclosure' => '"',
            'escape' => '"',
            'inputEncoding' => 'UTF-8',
            'outputEncoding' => 'ISO-8859-1',
            'writeHeaderLine' => true,
            'responseFilename' => 'export.csv',
        ),
    );

    /**
     * @param SecurityContext $securityContext
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->securityContext = $securityContext;
        $this->options['tempDir'] = sys_get_temp_dir().'/sygefor/';
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $entities = $this->getObjectList($idList);

        // accessor
        $accessor = PropertyAccess::createPropertyAccessor();
        // lines
        $lines = array();
        foreach ($entities as $entity) {
            if (!$this->securityContext->getToken()->getUser() instanceof User || $this->securityContext->isGranted('VIEW', $entity)) {
                $data = array();
	            foreach ($this->options['fields'] as $key => $value) {
		            try {
			            $rvalue = null;
			            try {
				            $rvalue = $accessor->getValue($entity, $key);
				            if (isset($value['subProperty'])) {
					            $subProperties = array();
					            $propertyAccessor = new PropertyAccessor();
					            foreach ($rvalue as $rval) {
						            try {
							            $subProperty = $accessor->getValue($rval, $value['subProperty']);
							            $subProperties[] = $subProperty;
						            } catch (\Exception $e) {
							            $subProperties[] = 'Non défini';
						            }
					            }
					            $rvalue = implode('|', $subProperties);
				            } elseif (!empty($value['type']) && $value['type'] == 'date') {
					            if ($rvalue) {
						            $rvalue = $rvalue->format('d/m/Y');
					            }
				            }
			            } catch (\Exception $e) {
			            }
			            $data[$key] = ($rvalue) ? $rvalue : '';
		            } catch (UnexpectedTypeException $e) {
		            }
	            }
                $lines[$entity->getId()] = $data;
            }
        }

        // fields
        $fields = array();

        foreach ($this->options['fields'] as $label => $value) {
            $fields[] = array($label, $value['label']);
        }

        //setting filename
        if (!empty($this->options['filename'])) {
            $this->options['volcanus_config']['responseFilename'] = $this->options['filename'];
        }

        $fileName = str_replace('.csv', '_'.uniqid().'.csv', $this->options['volcanus_config']['responseFilename']);
        $writer = new Writer($this->options['volcanus_config']);
        $writer->fields($fields);
        $file = new \SplFileObject($this->options['tempDir'].$fileName, 'w+');
        $writer->setFile($file);
        $writer->write($lines);
        //$writer->send();
        return array('fileUrl' => $fileName);
    }

    /**
     * Gets a file from module's temp dir if exists, and send it to client.
     *
     * @param $fileName
     *
     * @return string|Response
     */
    public function sendFile($fileName)
    {
        if (file_exists($this->options['tempDir'].$fileName)) {
            //security check first : if requested file path doesn't correspond to temp dir,
            //triggering error
            $path_parts = pathinfo($this->options['tempDir'].$fileName);
            $response = new Response();
            if (realpath($path_parts['dirname']) !== $this->options['tempDir']) {
                $response->setContent('Accès non autorisé :'.$path_parts['dirname']);
            }
            //if pdf file is asked

            $fp = $this->options['tempDir'].$fileName;

            // Set headers
            $response->headers->set('Cache-Control', 'private');
            $response->headers->set('Content-type', 'text/csv');
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'";');
            $response->headers->set('Content-length', filesize($fp));
            $response->sendHeaders();
            $response->setContent(readfile($fp));
            $response->sendContent();

            //file is then deleted
            unlink($fp);

            return $response;
        }

        return $this->options['tempDir'].$fileName;
    }
}
