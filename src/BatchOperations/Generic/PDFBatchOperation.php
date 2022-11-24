<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 07/04/14
 * Time: 16:56.
 */

namespace App\BatchOperations\Generic;


use Knp\Snappy\Pdf;
use App\BatchOperations\AbstractBatchOperation;
use App\Entity\Core\AbstractInscription;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractTraining;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

/**
 * Class PDFBatchOperation.
 */
class PDFBatchOperation extends AbstractBatchOperation
{
    /**
     * @var Pdf
     */
    protected $pdf;

    /**
     * @var string
     */
    protected $entityKey;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $defaultTemplate;

    /**
     * @var string
     */
    protected $templates;

    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $templateDiscriminator;

    /**
     * @var Security
     */
    protected $securityContext;

    protected $parameterBag;

    /**
     * PDFBatchOperation constructor.
     *
     * @param Pdf $pdf
     * @param Security   $securityContext
     * @param             $parameterBag
     */
    public function __construct(Pdf $pdf, Security $securityContext, Environment $twig, $parameterBag)
    {
        $this->pdf = $pdf;
        $this->securityContext = $securityContext;
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
/*        $this->pdf->getInternalGenerator()
            ->setTemporaryFolder(sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR);*/
    }

    /**
     * @param string $entityKey
     */
    public function setEntityKey($entityKey)
    {
        $this->entityKey = $entityKey;
    }

    /**
     * @param string $defaultTemplate
     */
    public function setDefaultTemplate($defaultTemplate)
    {
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * @param string $templates
     */
    public function setTemplates($templates)
    {
        $this->templates = $templates;
    }

    /**
     * @param string $templateDiscriminator
     */
    public function setTemplateDiscriminator($templateDiscriminator)
    {
        $this->templateDiscriminator = $templateDiscriminator;
    }

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $accessor = PropertyAccess::createPropertyAccessor();

        $entities = $this->getObjectList($idList);
        $pages = array();
        /*
        foreach ($entities as $entity) {
            // security check
            if ($this->securityContext->isGranted('VIEW', $entity)) {
                // determine the template
                $template = $this->defaultTemplate;
                if ($this->templateDiscriminator) {
                    $key = $accessor->getValue($entity, $this->templateDiscriminator);
                    if (isset($this->templates[$key])) {
                        $template = $this->templates[$key];
                    }
                }

                $signature = null;
                $training = null;
                if ($entity instanceof AbstractTraining) {
                    $training = $entity;
                } elseif ($entity instanceof AbstractSession) {
                    $training = $entity->getTraining();
                } elseif ($entity instanceof AbstractInscription) {
                    $training = $entity->getSession()->getTraining();
                }
                //checking signature file existence
                $fs = new Filesystem();
                if ($fs->exists($this->parameterBag->get('kernel.project_dir').'/../web/img/organization/'.$training->getOrganization()->getCode().'/signature.png')) {
                    $signature = '/img/organization/'.$training->getOrganization()->getCode().'/signature.png';
                }

                // render the page
                $vars = array();
                $vars[$this->entityKey] = $entity;
                $vars['link'] = $_SERVER['DOCUMENT_ROOT'];
                //prevent escaping quotes in rendered template.
                $vars['autoescape'] = false;
                $vars['signature'] = $signature;
                $pages[$entity->getId()] = $this->templating->render($template, $vars);
            }
        }

        // add a page break between each page
        $html = implode('<div style="page-break-after: always;"></div>', $pages);
        $filename = $this->filename ? $this->filename : 'file.pdf';

        // return the pdf
        return new Response(
            $this->pdf->getOutputFromHtml($html, array('print-media-type' => null)),
            200,
            array(
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            )
        );*/
        foreach ($entities as $entity) {
            // security check
            if ($this->securityContext->isGranted('VIEW', $entity)) {
                // determine the template
                $template = $this->defaultTemplate;
                if ($this->templateDiscriminator) {
                    $key = $accessor->getValue($entity, $this->templateDiscriminator);
                    if (isset($this->templates[$key])) {
                        $template = $this->templates[$key];
                    }
                }

                $signature = null;
                $training = null;
                if ($entity instanceof AbstractTraining) {
                    $training = $entity;
                } elseif ($entity instanceof AbstractSession) {
                    $training = $entity->getTraining();
                } elseif ($entity instanceof AbstractInscription) {
                    $inscription = $entity;
                    $session = $inscription->getSession();

                    // Gestion nombre d'heures de formation
                    // On crée le tableau de dates correspondant au tableau des présences
                    $tabDates = array();
                    $nbJoursDate2 = -1;
                    foreach ($session->getDates() as $dateSes) {
                        // Conversion date de début de session
                        $dateDeb = $dateSes->getDateBegin();
                        $dateNewS = $dateDeb->format('d/m/Y');
                        $tab = explode('/', $dateNewS);
                        $dateNew = new \DateTime();
                        $dateNew->setDate($tab[2], $tab[1], $tab[0]);

                        $nbJoursDate2 = date_diff($dateSes->getDateEnd(), $dateSes->getDateBegin());
                        $nbJoursDate = $nbJoursDate2->format('%a');
                        // création du tableau des dates suivant le nombre de jours à afficher
                        for ($j = 0; $j < $nbJoursDate + 1; $j++) {
                            $tabDates[] = array("dateDeb" => $dateNew->format('d/m/Y'), "nbHeuresMatin" => $dateSes->getHourNumberMorn(), "nbHeuresApr" => $dateSes->getHourNumberAfter());
                            $dateNew->modify('+ 1 days');

                        }
                    }

                    // calcul du nombre d'heures de présence effective
                    // On initialise le nombre d'heures de présence
                    $nbHeuresPresence = 0;
                    // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                    foreach ($inscription->getPresences() as $pres) {
                        foreach ($tabDates as $datePres) {
                            if ($pres->getDateBegin()->format('d/m/Y') == $datePres["dateDeb"]) {
                                if ($pres->getMorning() == "Présent") {
                                    $nbHeuresPresence += $datePres["nbHeuresMatin"];
                                }
                                if ($pres->getAfternoon() == "Présent") {
                                    $nbHeuresPresence += $datePres["nbHeuresApr"];
                                }
                                break;
                            }
                        }
                    }
                    $nbHeuresSession = $session->getHourNumber();

                    //filesystem for checking signature file existence

                    // getting signature asset
                    $signature = null;

                    //checking signature file existence
                    $fs = new Filesystem();
                    if ($fs->exists($this->parameterBag->get('kernel.project_dir') . '/public/img/organization/' . $inscription->getSession()->getTraining()->getOrganization()->getCode() . '/signature.png')) {
                        $signature = '/img/organization/' . $inscription->getSession()->getTraining()->getOrganization()->getCode() . '/signature.png';
                    }

                    $pdfView = $this->twig->render('PDF/attestation.pdf.twig', array(
                        'inscription' => $inscription,
                        'signature' => $signature,
                        'nbHeuresPresence' => $nbHeuresPresence . "/" . $nbHeuresSession
                    ));

                    return new Response(
                        $this->pdf->getOutputFromHtml($pdfView, array('print-media-type' => null)), 200,
                        array(
                            'Content-Type' => 'application/pdf',
                            'Content-Disposition' => 'attachment; filename="attestation.pdf"',)
                    );
                }
            }
        }
    }
}
