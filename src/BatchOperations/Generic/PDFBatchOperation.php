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
use App\Entity\Term\ImageFile;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Environment;

/**
 * Class PDFBatchOperation.
 */
final class PDFBatchOperation extends AbstractBatchOperation
{
    /**
     * @var string
     */
    private $defaultTemplate;

    /**
     * @var string
     */
    private $templates;

    /**
     * @var string
     */
    private $templateDiscriminator;

    /**
     * PDFBatchOperation constructor.
     *
     * @param             $parameterBag
     */
    public function __construct(protected Pdf $pdf, protected Security $security, protected Environment $twigEnvironment, protected $parameterBag)
    {
        /*        $this->pdf->getInternalGenerator()
            ->setTemporaryFolder(sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR);*/
    }

    /**
     * @param string $defaultTemplate
     */
    public function setDefaultTemplate($defaultTemplate): void
    {
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * @param string $templates
     */
    public function setTemplates($templates): void
    {
        $this->templates = $templates;
    }

    /**
     * @param string $templateDiscriminator
     */
    public function setTemplateDiscriminator($templateDiscriminator): void
    {
        $this->templateDiscriminator = $templateDiscriminator;
    }

    /**
     *
     * @return mixed
     */
    public function execute(array $idList = [], array $options = []): mixed
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $entities = $this->getObjectList($idList);
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
            if ($this->security->isGranted('VIEW', $entity)) {
                // determine the template
                $template = $this->defaultTemplate;
                if ($this->templateDiscriminator !== '' && $this->templateDiscriminator !== '0') {
                    $key = $propertyAccessor->getValue($entity, $this->templateDiscriminator);
                    if (isset($this->templates[$key])) {
                        $template = $this->templates[$key];
                    }
                }
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
                    $tabDates = [];
                    $nbJoursDate2 = -1;
                    foreach ($session->getDates() as $dateSes) {
                        // Conversion date de début de session
                        $dateDeb = $dateSes->getDateBegin();
                        $dateNewS = $dateDeb->format('d/m/Y');
                        $tab = explode('/', (string) $dateNewS);
                        $dateNew = new \DateTime();
                        $dateNew->setDate($tab[2], $tab[1], $tab[0]);

                        $nbJoursDate2 = date_diff($dateSes->getDateEnd(), $dateSes->getDateBegin());
                        $nbJoursDate = $nbJoursDate2->format('%a');
                        // création du tableau des dates suivant le nombre de jours à afficher
                        for ($j = 0; $j < $nbJoursDate + 1; ++$j) {
                            $tabDates[] = ["dateDeb" => $dateNew->format('d/m/Y'), "nbHeuresMatin" => $dateSes->getHourNumberMorn(), "nbHeuresApr" => $dateSes->getHourNumberAfter()];
                            $dateNew->modify('+ 1 days');

                        }
                    }

                    // calcul du nombre d'heures de présence effective
                    // On initialise le nombre d'heures de présence
                    $nbHeuresPresence = 0;
                    // Pour chaque presence, on compare avec le tableau des dates et on calcule le nombre d'heures
                    foreach ($inscription->getPresences() as $presence) {
                        foreach ($tabDates as $tabDate) {
                            if ($presence->getDateBegin()->format('d/m/Y') == $tabDate["dateDeb"]) {
                                if ($presence->getMorning() == "Présent") {
                                    $nbHeuresPresence += $tabDate["nbHeuresMatin"];
                                }

                                if ($presence->getAfternoon() == "Présent") {
                                    $nbHeuresPresence += $tabDate["nbHeuresApr"];
                                }

                                break;
                            }
                        }
                    }

                    $nbHeuresSession = $session->getHourNumber();

                    // Recuperation des fichiers logos et signature
                    $organization = $inscription->getOrganization();
                    $images = $this->doctrine->getRepository(\App\Entity\Term\ImageFile::class)->findBy(['organization' => $organization]);

                    //checking file existence
                    $fileSignature = null;
                    $fileLogo = null;
                    $filesystem = new Filesystem();
                    foreach ($images as $image) {
                        $fileName = $image->getName();
                        if(str_contains($fileName, 'logo') && $filesystem->exists($this->parameterBag->get('kernel.project_dir') . '/public/img/vocabulary/'.$image->getFilepath())){
                            $fileLogo = 'https://' . $this->parameterBag->get('front_host') . '/img/vocabulary/'.$image->getFilepath();
                        }

                        if(str_contains($fileName, 'signature') && $filesystem->exists($this->parameterBag->get('kernel.project_dir') . '/public/img/vocabulary/'.$image->getFilepath())){
                            $fileSignature = 'https://' . $this->parameterBag->get('front_host') . '/img/vocabulary/'.$image->getFilepath();
                        }
                    }

                    // patch pb encodage HTML
                    $firstNameTrainee = htmlentities($inscription->getTrainee()->getFirstname());
                    $inscription->getTrainee()->setFirstname($firstNameTrainee);
                    $lastNameTrainee = htmlentities($inscription->getTrainee()->getLastname());
                    $inscription->getTrainee()->setLastname($lastNameTrainee);
                    $orgName = htmlentities($session->getTraining()->getOrganization()->getName());
                    $session->getTraining()->getOrganization()->setName($orgName);
                    $orgAdr = htmlentities($session->getTraining()->getOrganization()->getAddress());
                    $session->getTraining()->getOrganization()->setAddress($orgAdr);
                    $orgCity = htmlentities($session->getTraining()->getOrganization()->getCity());
                    $session->getTraining()->getOrganization()->setCity($orgCity);
                    $nameForm = htmlentities($session->getTraining()->getName());
                    $session->getTraining()->setName($nameForm);
                    $trainers = $session->getTrainers();
                    foreach ($trainers as $trainer) {
                        $firstNameTrainer = htmlentities((string) $trainer->getFirstname());
                        $trainer->setFirstName($firstNameTrainer);
                        $lastNameTrainer = htmlentities((string) $trainer->getLastname());
                        $trainer->setLastname($lastNameTrainer);
                    }

                    $session->getTraining()->setName($nameForm);

                    $pdfView = $this->twigEnvironment->render('PDF/attestation.pdf.twig', ['inscription' => $inscription, 'nbHeuresPresence' => $nbHeuresPresence . "/" . $nbHeuresSession, 'logo' => $fileLogo, 'signature' => $fileSignature]);

                    return new Response(
                        $this->pdf->getOutputFromHtml($pdfView, ['print-media-type' => null]), \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                        ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="attestation.pdf"']
                    );
                }
            }
        }
    }
}
