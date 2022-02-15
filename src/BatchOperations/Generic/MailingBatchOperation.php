<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 28/04/14
 * Time: 10:41.
 */

namespace CoreBundle\BatchOperations\Generic;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;
use CoreBundle\Utils\ArrayFunctions;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Process\Exception\RuntimeException;
use CoreBundle\Entity\Term\PublipostTemplate;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use CoreBundle\BatchOperations\AbstractBatchOperation;
use CoreBundle\BatchOperations\BatchOperationModalConfigInterface;

/**
 * Class MailingBatchOperation.
 */
class MailingBatchOperation extends AbstractBatchOperation implements BatchOperationModalConfigInterface, ContainerAwareInterface
{
    /** @var string Current template as a filename */
    private $currentTemplateFileName;

    /** @string current tempalte filename */
    private $currentTemplate;

    /** @var Container service container */
    private $container;

    /** @var SecurityContext security Context */
    private $securityContext;

    /** @var array */
    protected $idList = array();

    /**
     * @param SecurityContext $securityContext
     *
     * @internal param $path
     */
    public function __construct(SecurityContext $securityContext)
    {
        $this->options['tempDir'] = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR;
        if (!file_exists($this->options['tempDir'])) {
            mkdir($this->options['tempDir'], 0777);
        }
        $this->securityContext = $securityContext;
    }

    /**
     * Get directory where generating file are written.
     *
     * @return string
     */
    public function getTempDir()
    {
        return isset($this->options['tempDir']) ? $this->options['tempDir'] : sys_get_temp_dir();
    }

    /**
     * make fields available.
     *
     * @return mixed
     */
    public function getFields()
    {
        return $this->options['fields'];
    }

    /**
     * Sets the Container.
     *
     * @param ContainerInterface|null $container A ContainerInterface instance or null
     *
     * @api
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * creates the result file and stores it on disk.
     *
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $this->idList = $idList;
        $entities = $this->getObjectList($idList);
        $this->setOptions($options);
        $deleteTemplate = false;

        //---setting choosed template file
        // 1/ File was provided by user
        if (isset($this->options['attachment']) && !empty($this->options['attachment'])) {
            $attachment = $this->options['attachment'][0];
            $attachment->move($this->options['tempDir'], $attachment->getClientOriginalName());
            $this->currentTemplate = $this->options['tempDir'].$attachment->getClientOriginalName();
            $this->currentTemplateFileName = $attachment->getClientOriginalName();
            $deleteTemplate = true;
        } elseif (isset($this->options['template']) && (is_integer($this->options['template']))) {
            //file was choosed in template list
            $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_publipost_template');
            /** @var EntityManager $em */
            $em = $this->em;
            $repo = $em->getRepository(get_class($templateTerm));
            /** @var PublipostTemplate[] $templates */
            $template = $repo->find($this->options['template']);

            $this->currentTemplate = $template->getAbsolutePath();
            $this->currentTemplateFileName = $template->getFileName();
        } else {
            // 3/ Error...
            return '';
        }

        $parseInfos = $this->parseFile($this->currentTemplate, $entities);

        if ($deleteTemplate) {
            unlink($this->currentTemplate);
        }

        return $parseInfos;
    }

    /**
     * Gets a file from module's temp dir if exists, and send it to client.
     *
     * @param $fileName
     * @param null  $outputFileName
     * @param array $options
     *
     * @internal param bool $pdf
     * @internal param bool $return
     *
     * @return string|Response
     */
    public function sendFile($fileName, $outputFileName = null, $options = array('pdf' => false, 'return' => false))
    {
        if (file_exists($this->options['tempDir'].$fileName)) {

            //security check first : if requested file path doesn't correspond to temp dir,
            //triggering error
            $path_parts = pathinfo($this->options['tempDir'].$fileName);

            $response = new Response();
            if (realpath($path_parts['dirname']) !== $this->options['tempDir']) {
                $response->setContent('Accès non autorisé :'.$path_parts['dirname']);
            }

            // setting output file name
            $outputFileName = (empty($outputFileName)) ? $fileName : $outputFileName;
            //if pdf file is asked
            if (isset($options['pdf']) && $options['pdf']) {
                $pdfName = $this->toPdf($fileName);
                $fp = $this->options['tempDir'].$pdfName;

                //renaming output filename (for end user)
                $tmp = explode('.', $outputFileName);
                $tmp[count($tmp) - 1] = 'pdf';
                $outputFileName = implode('.', $tmp);
            } else {
                $fp = $this->options['tempDir'].$fileName;
            }

            if (isset($options['return']) && $options['return']) {
                $file = new File($fp);

                return $file->move($file->getFileInfo()->getPath(), $outputFileName);
            } else {
                // Set headers
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $response->headers->set('Cache-Control', 'private');
                $response->headers->set('Content-type', finfo_file($finfo, $fp));
                $response->headers->set('Content-Disposition', 'attachment; filename="'.$outputFileName.'";');
                $response->headers->set('Content-length', filesize($fp));
                $response->sendHeaders();
                $response->setContent(readfile($fp));
                $response->sendContent();

                // file is then deleted
                unlink($fp);

                return $response;
            }
        }

        return '';
    }

    /**
     * @param string|PublipostTemplate $template $template
     * @param array                    $entities
     *
     * @return array
     */
    public function parseFile($template, $entities)
    {
        list($TBS, $classCatalog) = $this->initializeOpenTbs($template, $entities);
        $lines = $this->getTemplateLines($entities);
        $errors = $this->mergeLinesWithPublipostTemplate($TBS, $lines);
        if (count($errors) > 0) {
            return $errors;
        }
        $this->computeShorcutsAndMerge($TBS, $entities, $classCatalog);
        $fileName = $this->generateFinalFile($TBS, $template);

        return array('fileUrl' => $fileName);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    public function getModalConfig($options = array())
    {
        $templateTerm = $this->container->get('sygefor_core.vocabulary_registry')->getVocabularyById('sygefor_core.vocabulary_publipost_template');

        /** @var EntityManager $em */
        $em = $this->em;
        $repo = $em->getRepository(get_class($templateTerm));
        /** @var PublipostTemplate[] $templates */
        $templates = $repo->findBy(array('organization' => $this->securityContext->getToken()->getUser()->getOrganization()));

        $files = array();
        foreach ($templates as $template) {
            $templateEntity = $template->getEntity();
            $ancestor = class_parents($this->targetClass);
            //file is added if its associated entity is an ancestor for current target class
            if ($templateEntity === $this->targetClass || in_array($templateEntity, $ancestor, true)) {
                $files[] = array('id' => $template->getId(), 'name' => $template->getName(), 'fileName' => $template->getFileName());
            }
        }

        return array('templateList' => $files);
    }

    /**
     * @param $fileName
     * @param null $outputFileName
     *
     * @return string pdf file name, or null if error
     */
    public function toPdf($fileName, $outputFileName = null)
    {
        if (empty($outputFileName)) {
            $outputFileName = $fileName;
        }

        //renaming output filename (for end user)
        $info = pathinfo($outputFileName);
        $outputFileName = $info['filename'].'.pdf';

        // prepare the process
        $unoconvBin = $this->container->getParameter('unoconv_bin');
        $args = array(
            $unoconvBin,
            '--output='.$this->options['tempDir'].$outputFileName,
            $this->options['tempDir'].$fileName,
        );
        $process = new Process(implode(' ', $args));

        // run
        try {
            $process->run();
        } catch (RuntimeException $exception) {
            // unoconv somtimes returns 8 (SIGFPE) error code but still produces a correct output,
            // so we can ignore it.
//            if ($exception->getCode() !== 8) {
//                throw $exception;
//            }
        }

        if (!empty($process->getErrorOutput())) {
            throw new RuntimeException('The PDF file has not been generated : '.$process->getErrorOutput());
        }

        return $outputFileName;
    }

    /**
     * @param $template
     * @param $entities
     *
     * @return \clsTinyButStrong
     *
     * @throws \Throwable
     */
    protected function initializeOpenTbs($template, $entities)
    {
        $TBS = $this->container->get('opentbs');
        $TBS->setOption('noerr', true);
        $TBS->LoadTemplate($template, OPENTBS_ALREADY_UTF8);
        $classCatalog = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getTermCatalog(get_class(current($entities)));

        return array($TBS, $classCatalog);
    }

    /**
     * @param $entities
     *
     * @return array
     *
     * @throws \Throwable
     */
    protected function getTemplateLines($entities)
    {
        $lines = array();
        foreach ($entities as $entity) {
            if ($this->securityContext->isGranted('VIEW', $entity)) {
                $lines[$entity->getId()] = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($entity);
            }
        }

        return $lines;
    }

    /**
     * @param \clsTinyButStrong $TBS
     * @param array             $lines
     *
     * @return array
     *
     * @throws \Exception
     * @throws \Throwable
     */
    protected function mergeLinesWithPublipostTemplate($TBS, $lines)
    {
        ob_start();
        $alias = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getEntityAlias($this->targetClass);
        $entityName = ($alias !== null) ? $alias : 'entity';
        $TBS->MergeBlock($entityName, $lines);
        // add global variable matching first entity
        if (count($lines) > 0) {
	        /**
	         * We need to handle empty arrays ourselves, or TBS will cast them to string "Array".
	         * @see  vendor/mbence/opentbs-bundle/MBence/OpenTBSBundle/lib/tbs_class.php:3360
	         * @var  array  $Value  Named after TBS convention
	         */
	        $Value = ArrayFunctions::emptyArraysToStringsRecursive(
		        reset($lines)->toArray()
	        );

	        $TBS->MergeField('global', $Value);
        }
        $error = ob_get_flush();

        return $error ? array('error' => $error) : array();
    }

    /**
     * Add global variables with publipost shorcuts.
     *
     * @param \clsTinyButStrong $TBS
     * @param array             $entities
     * @param string            $classCatalog
     *
     * @return int
     *
     * @throws \Throwable
     */
    protected function computeShorcutsAndMerge($TBS, $entities, $classCatalog)
    {
        $i = 0;
        if (isset($classCatalog['shorcuts'])) {
            $aliases = $classCatalog['shorcuts'];

            $propertyAccessor = new PropertyAccessor();
            foreach ($aliases as $alias => $params) {
                $arrayValues = array();
                $max = count($entities);
                if (isset($params['current']) && $params['current'] === true && $max > 0) {
                    $max = 1;
                }
                $i = 0;
                $keys = array_keys($entities);
                // get only current entity value with path or all entities values with path
                while ($i < $max) {
                    try {
                        $val = $propertyAccessor->getValue($entities[$keys[$i]], $params['path']);
                        // create an array collection to simplify work in foreach
                        $collection = new ArrayCollection();
                        if (is_object($val) && $val instanceof ArrayCollection) {
                            $collection = $val;
                        } else {
                            $collection->add($val);
                        }
                        // get human readable accessor foreach value
                        foreach ($collection as $item) {
                            $accessor = $this->container->get('sygefor_core.human_readable_property_accessor_factory')->getAccessor($item);
                            if (is_object($item) && method_exists($item, 'getId')) {
                                $id = $item->getId();
                                $arrayValues[$id] = $accessor;
                            } else {
                                $arrayValues[] = $accessor;
                            }
                        }
                    } catch (\Exception $e) {
                    }
                    ++$i;
                }

                if (isset($params['sort']) && $params['sort']) {
                    usort($arrayValues, function ($a, $b) use ($params, $propertyAccessor) {
                        return $propertyAccessor->getValue($a, $params['sort']) > $propertyAccessor->getValue($b, $params['sort']);
                    });
                }

                $TBS->MergeBlock($alias, $arrayValues);
                ++$i;
            }
        }

        return $i;
    }

    /**
     * @param \clsTinyButStrong        $TBS
     * @param string|PublipostTemplate $template
     *
     * @return mixed|string
     */
    protected function generateFinalFile($TBS, $template)
    {
        $uid = uniqid();
        $fileName = empty($this->currentTemplateFileName) ? $template->getFileName() : $this->currentTemplateFileName;
        $uniqFileName = $this->removeAccents($uid.'_'.$fileName);
        $TBS->Show(OPENTBS_FILE, $this->options['tempDir'].$uniqFileName);
        $TBS->_PlugIns[OPENTBS_PLUGIN]->Close();

        return $uniqFileName;
    }

    /**
     * @param $str
     * @param string $charset
     *
     * @return mixed|string
     */
    private function removeAccents($str, $charset = 'utf-8')
    {
        //converting to html elements
        $str = htmlentities($str, ENT_NOQUOTES, $charset);

        //keeping only first char after '&', so that &eacute becomes e for example
        $str = preg_replace('#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
        $str = preg_replace('#&[^;]+;#', '', $str); // removing not recognized chars

        $str = str_replace(' ', '_', $str);
        $str = str_replace('\'', '-', $str);

        return $str;
    }
}
