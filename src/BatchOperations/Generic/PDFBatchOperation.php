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
    private string $defaultTemplate = 'PDF/attestation.pdf.twig';

    /**
     * @var string
     */
    private string $templates;

    /**
     * @var string
     */
    private string $templateDiscriminator = "";

    /**
     * PDFBatchOperation constructor.
     *
     * @param             $parameterBag
     */
    public function __construct( protected Pdf $pdf, protected Security $security, protected Environment $twigEnvironment, protected $parameterBag)
    {

        parent::__construct();
        /*        $this->pdf->getInternalGenerator()
            ->setTemporaryFolder(sys_get_temp_dir().DIRECTORY_SEPARATOR.'sygefor'.DIRECTORY_SEPARATOR);*/

    }

    /**
     * @param string $defaultTemplate
     */
    public function setDefaultTemplate(string $defaultTemplate): void
    {
        $this->defaultTemplate = $defaultTemplate;
    }

    /**
     * @param string $templates
     */
    public function setTemplates(string $templates): void
    {
        $this->templates = $templates;
    }

    /**
     * @param string $templateDiscriminator
     */
    public function setTemplateDiscriminator(string $templateDiscriminator): void
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

        foreach ($entities as $entity) {
            if (!$this->security->isGranted('VIEW', $entity)) {
                continue;
            }

            $template = $this->defaultTemplate;
            $vars = [];
            $filename = 'attestation.pdf';

            if ($entity instanceof AbstractTraining) {
                $template = 'PDF/training.pdf.twig';
                $vars = [
                    'training' => $entity,
                    'logo' => null,
                    'signature' => null,
                ];
                $filename = 'training.pdf';
            }

            elseif ($entity instanceof AbstractSession) {
                $training = $entity->getTraining();
                $template = 'PDF/session.pdf.twig';
                $organization = $training->getOrganization();
                $images = $this->doctrine->getRepository(ImageFile::class)->findBy(['organization' => $organization]);

                $filesystem = new Filesystem();
                $fileLogo = null;
                $fileSignature = null;

                foreach ($images as $image) {
                    $filepath = $this->parameterBag->get('kernel.project_dir') . '/public/img/vocabulary/' . $image->getFilepath();
                    $urlBase = 'https://' . $this->parameterBag->get('front_host') . '/img/vocabulary/' . $image->getFilepath();

                    if (str_contains($image->getName(), 'logo') && $filesystem->exists($filepath)) {
                        $fileLogo = $urlBase;
                    }

                    if (str_contains($image->getName(), 'signature') && $filesystem->exists($filepath)) {
                        $fileSignature = $urlBase;
                    }
                }

                $vars = [
                    'training' => $training,
                    'logo' => $fileLogo,
                    'signature' => $fileSignature
                ];

                $filename = 'session.pdf';
            }

            elseif ($entity instanceof AbstractInscription) {
                $inscription = $entity;
                $session = $inscription->getSession();
                $training = $session->getTraining();

                // === Calcul des heures de présence ===
                $tabDates = [];
                foreach ($session->getDates() as $dateSes) {
                    $start = clone $dateSes->getDateBegin();
                    $interval = $dateSes->getDateEnd()->diff($start)->days;
                    for ($i = 0; $i <= $interval; ++$i) {
                        $tabDates[] = [
                            "dateDeb" => $start->format('d/m/Y'),
                            "nbHeuresMatin" => $dateSes->getHourNumberMorn(),
                            "nbHeuresApr" => $dateSes->getHourNumberAfter()
                        ];
                        $start->modify('+1 day');
                    }
                }

                $nbHeuresPresence = 0;
                foreach ($inscription->getPresences() as $presence) {
                    foreach ($tabDates as $tabDate) {
                        if ($presence->getDateBegin()->format('d/m/Y') === $tabDate["dateDeb"]) {
                            if ($presence->getMorning() === "Présent") {
                                $nbHeuresPresence += $tabDate["nbHeuresMatin"];
                            }
                            if ($presence->getAfternoon() === "Présent") {
                                $nbHeuresPresence += $tabDate["nbHeuresApr"];
                            }
                            break;
                        }
                    }
                }

                $nbHeuresSession = $session->getHourNumber();

                // === Récupération logo & signature ===
                $organization = $inscription->getOrganization();
                $images = $this->doctrine->getRepository(ImageFile::class)->findBy(['organization' => $organization]);

                $filesystem = new Filesystem();
                $fileLogo = null;
                $fileSignature = null;

                foreach ($images as $image) {
                    $filepath = $this->parameterBag->get('kernel.project_dir') . '/public/img/vocabulary/' . $image->getFilepath();
                    $urlBase = 'https://' . $this->parameterBag->get('front_host') . '/img/vocabulary/' . $image->getFilepath();

                    if (str_contains($image->getName(), 'logo') && $filesystem->exists($filepath)) {
                        $fileLogo = $urlBase;
                    }

                    if (str_contains($image->getName(), 'signature') && $filesystem->exists($filepath)) {
                        $fileSignature = $urlBase;
                    }
                }

                // === Encodage HTML sécurité ===
                $trainee = $inscription->getTrainee();
                $trainee->setFirstname(htmlentities($trainee->getFirstname()));
                $trainee->setLastname(htmlentities($trainee->getLastname()));

                $organization = $training->getOrganization();
                $organization->setName(htmlentities($organization->getName()));
                $organization->setAddress(htmlentities($organization->getAddress()));
                $organization->setCity(htmlentities($organization->getCity()));
                $training->setName(htmlentities($training->getName()));

                foreach ($session->getTrainers() as $trainer) {
                    $trainer->setFirstname(htmlentities((string) $trainer->getFirstname()));
                    $trainer->setLastname(htmlentities((string) $trainer->getLastname()));
                }

                $template = 'PDF/attestation.pdf.twig';
                $vars = [
                    'inscription' => $inscription,
                    'nbHeuresPresence' => $nbHeuresPresence . '/' . $nbHeuresSession,
                    'logo' => $fileLogo,
                    'signature' => $fileSignature
                ];
                $filename = 'attestation.pdf';
            }

            // === Génération du PDF ===
            $html = $this->twigEnvironment->render($template, $vars);
            $pdfOutput = $this->pdf->getOutputFromHtml($html, ['print-media-type' => null]);

            return new Response(
                $pdfOutput,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="' . $filename . '"'
                ]
            );
        }

        // Si aucun PDF généré
        return ['fileUrl' => null];
    }

}
