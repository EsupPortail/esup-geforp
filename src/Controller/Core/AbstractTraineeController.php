<?php

namespace App\Controller\Core;

use App\AccessRight\AccessRightRegistry;
use App\Entity\Back\Institution;
use App\Entity\Back\Trainee;
use App\Entity\Back\Organization;
use App\Entity\Term\Publictype;
use App\Entity\Term\Title;
use App\Form\Type\AbstractTraineeType;
use App\Repository\TraineeSearchRepository;
use Doctrine\Persistence\ManagerRegistry;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Core\AbstractTrainee;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use App\Form\Type\ChangeOrganizationType;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
/**
 * Class TraineeController.
 *
 */
#[Route(path: '/trainee')]
abstract class AbstractTraineeController extends AbstractController
{
    /**
     * @var string
     */
    protected $traineeClass = AbstractTrainee::class;

    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/search', name: 'trainee.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, TraineeSearchRepository $traineeSearchRepository, AccessRightRegistry $accessRightRegistry): array
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters')  ?: [];
        $query_filters = $request->request->all('query_filters') ?: [];
        $aggs = $request->request->all('aggs') ?:[] ;
        $query = $request->request->all('query') ?:[];
        $page = $request->request->get('page', 1);
        $size = $request->request->get('size', 10);
        $sorts = $request->request->all('sorts') ?:[];
        $fields = $request->request->all('fields') ?:[];

        // security check : trainee : 'sygefor_trainee.rights.trainee.all.view' -> id=17
        if(!$accessRightRegistry->hasAccessRight(17)) {
            // recup établissement du user
            $ownInst = $this->getUser()->getOrganization()->getInstitution();
            // recup des établissements liés
            $otherInst = $this->getUser()->getOrganization()->getInstitution()->getVisuinstitutions();
            if (isset($filters['institution.name.source'])) {
                if (is_array($filters['institution.name.source'])) {
                    // si filtre sur plusieurs etablissements, on verifie les droits en visibilite
                    $tabFilters = [];
                    // on parcourt les filtres pour vérifier qu'on a la visibilité sur les établissements
                    foreach ($filters['institution.name.source'] as $filter) {
                        if ($filter == $ownInst->getName())
                            $tabFilters[] = $filter;
                        else {
                            foreach ($otherInst as $etab) {
                                if ($filter == $etab->getName()) {
                                    $tabFilters[] = $filter;
                                    break;
                                }
                            }
                        }
                    }

                    $filters['institution.name.source'] = $tabFilters;
                } elseif ($filters['institution.name.source'] == $ownInst->getName()) {
                    // si filtre sur un seul etablissement, on verifie les droits
                    // on verifie l'établissement du  user
                    $tabFilters[] = $filters['institution.name.source'];
                } else {
                    // sinon, on regarde les etablissements associés
                    $flag = 0;
                    foreach ($otherInst as $etab) {
                        if ($filters['institution.name.source'] == $etab->getName()) {
                            $flag = 1;
                            break;
                        }
                    }

                    // si pas autorisé, par défaut, on met le filtre sur établissement du user
                    if ($flag==0)
                        $filters['institution.name.source'] = $this->getUser()->getOrganization()->getInstitution()->getName();
                }
            } else {
                // restriction to user's institution
                $filters['institution.name.source'] = [];
                $filters['institution.name.source'][] = $this->getUser()->getOrganization()->getInstitution()->getName();
                if ($otherInst != null) {
                    foreach ($otherInst as $otherEtab) {
                        $filters['institution.name.source'][] = $otherEtab->getName();
                    }
                }
            }
        }

        // Recherche avec les filtres
        $ret = $traineeSearchRepository->getTraineesList($keywords, $filters, $page, $size, $sorts, $fields);
        $tabAggs = $this->constructAggs($aggs, $keywords, $query_filters, $managerRegistry, $traineeSearchRepository);

        // Recherche avec query (pour autocompletion)
        // on transforme le champ 'query' en 'keywords'
        if (isset($query['match']['fullname.autocomplete']['query'])) {
            $keywords = $query['match']['fullname.autocomplete']['query'];
            $ret = $traineeSearchRepository->getTraineesList($keywords, $filters, $page, $size, $sorts, $fields);
        }

        // Concatenation des resultats
        $ret['aggs'] = $tabAggs;

        return $ret;
    }


    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/create', name: 'trainee.create', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function create(Request $request, ManagerRegistry $managerRegistry): array
    {
        /** @var AbstractTrainee $trainee */
        $trainee = new $this->traineeClass();
        // Ajout de l'établissement du trainee que l'on crée
        try {
            $trainee->setInstitution($this->getUser()->getOrganization()->getInstitution());
        } catch (\Exception $exception) {
            return [$exception->getMessage()];
        }

        //trainee can't be created if user has no rights for it
        if (!$this->isGranted('CREATE', $trainee)) {
            throw new AccessDeniedException('Action non autorisée');
        }

        $form = $this->createForm(AbstractTraineeType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $trainee->setCreatedAt(new \DateTime('now'));
                $trainee->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($trainee);
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'trainee' => $trainee];
    }


    #[Route(path: '/{id}/view', name: 'trainee.view', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'])]
    #[IsGranted('VIEW', subject: 'trainee')]
    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    public function view(Request $request,  ManagerRegistry $managerRegistry, AbstractTrainee $trainee, int $id): array
    {
        $trainee = $managerRegistry->getRepository(Trainee::class)->find($id);
        if (!$trainee) {
            throw new NotFoundHttpException('Trainee not found');
        }
        // access right is checked inside controller, so to be able to send specific error message
        if (!$this->isGranted('EDIT', $trainee)) {
            if ($this->isGranted('VIEW', $trainee)) {
                return ['trainee' => $trainee];
            }

            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $form = $this->createForm(AbstractTraineeType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $trainee->setUpdatedAt(new \DateTime('now'));
                $objectManager = $managerRegistry->getManager();
                $objectManager->persist($trainee);
                $objectManager->flush();
            }
        }

        return ['form' => $form->createView(), 'trainee' => $trainee];
    }

    #[Groups(['Default', 'trainee'])]
    #[Route(path: '/{id}/toggleActivation', name: 'trainee.toggleActivation', requirements: ['id' => '\d+'], options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    public function toggleActivation(AbstractTrainee $trainee, ManagerRegistry $managerRegistry, int $id): array
    {
        $trainee = $managerRegistry->getRepository(Trainee::class)->find($id);
        if (!$trainee) {
            throw new NotFoundHttpException('Trainee not found');
        }
        //access right is checked inside controller, so to be able to send specific error message
        if (!$this->isGranted('EDIT', $trainee)) {
            throw new AccessDeniedException("Vous n'avez pas accès aux informations détaillées de cet utilisateur");
        }

        $trainee->setIsactive(!$trainee->getIsactive());
        $this->managerRegistry->getManager()->flush();

        return ['trainee' => $trainee];
    }


    #[Route(path: '/{id}/remove', name: 'trainee.delete', options: ['expose' => true], defaults: ['_format' => 'json'], methods: ['POST'])]
    #[IsGranted('DELETE', subject: 'trainee')]
    #[Rest\View(serializerGroups: ['Default', 'trainee'], serializerEnableMaxDepthChecks: true)]
    public function delete(AbstractTrainee $trainee, ManagerRegistry $managerRegistry ,int $id): array
    {
        $trainee = $managerRegistry->getRepository(Trainee::class)->find($id);
        if (!$trainee) {
            throw new NotFoundHttpException('Trainee not found');
        }
        $objectManager = $managerRegistry->getManager();
        $objectManager->remove($trainee);
        $objectManager->flush();

        return [];
    }

    private function constructAggs($aggs, $keyword, $query_filters, \Doctrine\Persistence\ManagerRegistry $managerRegistry, \App\Repository\TraineeSearchRepository $traineeSearchRepository): array
    {
        $tabAggs = [];

        // CONSTRUCTION CIVILITE
        if(isset( $aggs['title'])){
            $allTitles = $managerRegistry->getRepository(Title::class)->findAll();

            $i = 0; $tabTitles = [];
            //Pour chaque civilité on teste la requête
            foreach($allTitles as $allTitle){
                $nbTraineesTitles = $traineeSearchRepository->getNbTrainees($query_filters, $keyword, $aggs, $allTitle->getName());
                if ($nbTraineesTitles > 0) {
                    $tabTitles[$i] = [ 'key' => $allTitle->getName(), 'doc_count' => $nbTraineesTitles];
                    ++$i;
                }
            }

            $tabAggs['title']['buckets'] = $tabTitles;
        }

        // CONSTRUCTION ETABLISSEMENT
        if (isset($aggs['institution.name.source'])) {
            $allInst = $managerRegistry->getRepository(Institution::class)->findAll();

            $i = 0; $tabInst = [];
            //Pour chaque établissement on teste la requête
            foreach($allInst as $inst){
                $nbTraineesInst = $traineeSearchRepository->getNbTrainees($query_filters, $keyword, $aggs, $inst->getName());
                if ($nbTraineesInst > 0) {
                    $tabInst[$i] = [ 'key' => $inst->getName(), 'doc_count' => $nbTraineesInst];
                    ++$i;
                }
            }

            $tabAggs['institution.name.source']['buckets'] = $tabInst;
        }

        // CONSTRUCTION PUBLIC TYPE
        if(isset( $aggs['publicType.source'])){
            $allPublictypes = $managerRegistry->getRepository(Publictype::class)->findAll();

            $i = 0; $tabPublicTypes = [];
            //Pour chaque public type on teste la requête
            foreach($allPublictypes as $allPublictype){
                $nbTraineesPt = $traineeSearchRepository->getNbTrainees($query_filters, $keyword, $aggs, $allPublictype->getName());
                if ($nbTraineesPt > 0) {
                    $tabPublicTypes[$i] = [ 'key' => $allPublictype->getName(), 'doc_count' => $nbTraineesPt];
                    ++$i;
                }
            }

            $tabAggs['publicType.source']['buckets'] = $tabPublicTypes;
        }

        return $tabAggs;
    }
}
