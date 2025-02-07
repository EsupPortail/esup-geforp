<?php

namespace App\Controller\Core;

use App\Entity\Term\Actiontype;
use App\Entity\Term\Emailtemplate;
use App\Entity\Term\Evaluationcriterion;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\MenuItem;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Term\Sessiontype;
use App\Entity\Term\Supervisor;
use App\Entity\Term\Tag;
use App\Entity\Term\Theme;
use App\Entity\Term\Title;
use App\Entity\Term\Trainertype;
use App\Entity\Term\Trainingcategory;
use App\Entity\Back\Organization;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Term\AbstractTerm;
use App\Entity\Term\Publiposttemplate;
use App\Entity\Term\TreeTrait;
use App\Form\Type\VocabularyType;
use App\Vocabulary\VocabularyInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class TaxonomyController.
 *
 */
#[Route(path: '/admin/taxonomy')]final class TaxonomyController extends AbstractController
{
    #[Route(path: '/', name: 'taxonomy.index')]
    public function index(ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry): \Symfony\Component\HttpFoundation\Response
    {
        if (!$this->isGranted('VIEW', VocabularyInterface::class)) {
            throw new AccessDeniedException();
        }

        return $this->render('Core/views/Taxonomy/index.html.twig', ['vocabularies' => $this->getVocabulariesList($vocabularyRegistry), 'organization' => $this->getUser()->getOrganization()]);
    }


    #[Route(path: '/{vocabularyId}/view/{organizationId}', name: 'taxonomy.view', defaults: ['organizationId' => null])]
    public function viewVocabulary(ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry, $vocabularyId, int $id, AbstractOrganization $organization = null): \Symfony\Component\HttpFoundation\Response
    {
        $organization = $managerRegistry->getRepository(AbstractOrganization::class)->find($id);
        if (!$organization) {
            throw new NotFoundHttpException();
        }
        /** @var AbstractTerm $abstractVocabulary */
        $abstractVocabulary = $vocabularyRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);
        // for mixed vocabularies
        $canEditNationalTerms = $this->isGranted('VIEW', $abstractVocabulary);

        if ($abstractVocabulary->getVocabularyStatus() === VocabularyInterface::VOCABULARY_LOCAL && !$organization) {
            return $this->redirectToRoute('taxonomy.view', ['vocabularyId' => $vocabularyId, 'organizationId' => $this->getUser()->getOrganization()->getId()]);
        }

        // set organization to abstract vocabulary to check access rights
        $abstractVocabulary->setOrganization($organization);
        if (!$this->isGranted('VIEW', $abstractVocabulary) && !$canEditNationalTerms) {
            // organization required for local vocabularies
            throw new AccessDeniedException('');
        }

        // needed for template organization tabs
        $organizations = [];
        $alterOrganizations = $managerRegistry->getManager()->getRepository(AbstractOrganization::class)->findAll();
        $alterAbstractVocabulary = $vocabularyRegistry->getVocabularyById($vocabularyId);
        foreach ($alterOrganizations as $alterOrganization) {
            $alterAbstractVocabulary->setOrganization($alterOrganization);
            if ($this->isGranted('VIEW', $alterAbstractVocabulary)) {
                $organizations[$alterOrganization->getId()] = $alterOrganization;
            }
        }

        $terms = $this->getRootTerms($managerRegistry, $abstractVocabulary, $organization);
        if ($organization instanceof \App\Entity\Core\AbstractOrganization) {
            foreach ($terms as $key => $term) {
                if (!$term->getOrganization()) {
                    unset($terms[$key]);
                }
            }
        }

        return $this->render('Core/views/Taxonomy/view.html.twig', ['organization' => $organization, 'organizations' => $organizations, 'canEditNationalTerms' => $canEditNationalTerms, 'terms' => $terms, 'vocabulary' => $abstractVocabulary, 'vocabularies' => $this->getVocabulariesList($vocabularyRegistry), 'sortable' => $abstractVocabulary::orderBy() === 'position', 'depth' => method_exists($abstractVocabulary, 'getChildren') ? 2 : 1]);
    }

    #[Route(path: '/{vocabularyId}/edit/{id}/{organizationId}', name: 'taxonomy.edit', defaults: ['id' => null, 'organizationId' => null])]
    public function editVocabularyTerm(Request $request, ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry, $vocabularyId, $organizationId, $id = null): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $organization = null;
        if ($organizationId) {
            $organization = $managerRegistry->getManager()->getRepository(AbstractOrganization::class)->find($organizationId);
        }

        $term = null;
        $abstractVocabulary = $vocabularyRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);

        $termClass = $abstractVocabulary::class;
        $objectManager = $managerRegistry->getManager();

        // find term
        if ($id) {
            $term = $objectManager->find($termClass, $id);
        }

        // create term if not found
        if (!$term instanceof \App\Vocabulary\VocabularyInterface) {
            $term = new $termClass();
            $term->setOrganization($organization);
        }

        if (!$this->isGranted('EDIT', $term)) {
            throw new AccessDeniedException();
        }

        // get term from
        $formType = VocabularyType::class;
        if (method_exists($abstractVocabulary, 'getFormType')) {
            $formType = $abstractVocabulary::getFormType();
        }

        $form = $this->createForm($formType, $term);

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if (($form->isSubmitted()) && ($form->isValid())) {
                $term->setOrganization($organization);
                $objectManager->persist($term);
                $objectManager->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le terme a bien été enregistré.');

                $organization_id = null;
                if ($organization instanceof \App\Entity\Core\AbstractOrganization) {
                    $organization_id = $organization->getId();
                }

                return $this->redirectToRoute('taxonomy.view', ['vocabularyId' => $vocabularyId, 'organizationId' => $organization_id]);
            }
        }

        return $this->render('Core/views/Taxonomy/edit.html.twig', ['vocabulary' => $abstractVocabulary, 'organization' => $organization, 'term' => $term, 'id' => $id, 'form' => $form->createView(), 'vocabularies' => $this->getVocabulariesList($vocabularyRegistry)]);
    }

    #[Route(path: '/{vocabularyId}/remove/{id}', name: 'taxonomy.remove')]
    public function remove(Request $request, ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry, $vocabularyId, $id): \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $abstractVocabulary = $vocabularyRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);

        $termClass = $abstractVocabulary::class;
        $objectManager = $managerRegistry->getManager();

        // find term
        $vocabulary = $objectManager->find($termClass, $id);
        if (!$vocabulary instanceof \App\Vocabulary\VocabularyInterface) {
            throw new NotFoundHttpException();
        }

        // protected term because needed for special system operations
        if ($vocabulary->isLocked()) {
            throw new AccessDeniedException("This term can't be removed");
        }

        if (!$this->isGranted('REMOVE', $vocabulary)) {
            throw new AccessDeniedException();
        }

        // get term usage
        $count = $vocabularyRegistry->getTermUsages($objectManager, $vocabulary);

        $formBuilder = $this->createFormBuilder(null, ['validation_groups' => ['taxonomy_term_remove']]);
        $notBlank = new NotBlank(['message' => 'Vous devez sélectionner un terme de substitution']);
        $notBlank->addImplicitGroupName('taxonomy_term_remove');

        // build query
        $queryBuilder = $objectManager->createQueryBuilder('s')
            ->select('t')
            ->from($termClass, 't')
            ->where('t.id != :id')->setParameter('id', $id)
            ->orderBy('t.'.$abstractVocabulary::orderBy());
        if ($vocabulary->getOrganization() instanceof \App\Entity\Back\Organization) {
            $queryBuilder
                ->andWhere('t.organization = :organization')
                ->setParameter('organization', $vocabulary->getOrganization());
        }

        $queryBuilder->orWhere('t.organization is null');

        //if entities are linked to current
        if ($count > 0) {
            $required = !empty($abstractVocabulary::$replacementRequired);
            $formBuilder
                ->add('term', 'entity',
                    ['class' => $termClass, 'expanded' => true, 'label' => 'Terme de substitution', 'required' => $required, 'constraints' => $required ? $notBlank : null, 'query_builder' => $queryBuilder, 'empty_value' => $required ? null : '- Aucun -']
                );
        }

        $organization_id = null;
        if ($vocabulary->getOrganization() instanceof \App\Entity\Back\Organization) {
            $organization_id = $vocabulary->getOrganization()->getId();
        }

        $form = $formBuilder->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($form->has('term')) {
                    $newTerm = $form->get('term')->getData();
                    if ($newTerm) {
                        $vocabularyRegistry->replaceTermInUsages(
                            $objectManager,
                            $vocabulary,
                            $newTerm);
                    }
                }

                $objectManager->remove($vocabulary);
                $objectManager->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le terme a bien été supprimé.');

                return $this->redirectToRoute('taxonomy.view', ['vocabularyId' => $vocabularyId, 'organizationId' => $organization_id]);
            }
        }

        return $this->render('Core/views/Taxonomy/remove.html.twig', ['vocabulary' => $abstractVocabulary, 'organization' => $vocabulary->getOrganization(), 'organization_id' => $organization_id, 'term' => $vocabulary, 'vocabularies' => $this->getVocabulariesList($vocabularyRegistry), 'count' => $count, 'form' => $form->createView()]);
    }

    #[Route(path: '/{vocabulary}/terms/order', name: 'taxonomy.terms_order', options: ['expose' => true], defaults: ['_format' => 'json'], methods: 'POST')]
    public function termsOrder(ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry, $vocabulary, Request $request): void
    {
        $abstractVocabulary = ${$vocabularyRegistry}->getVocabularyById($vocabulary);
        $abstractVocabulary->setVocabularyId($vocabulary);

        $termClass = $abstractVocabulary::class;

        $objectManager = $managerRegistry->getManager();
        $objectRepository = $objectManager->getRepository($termClass);
        $serialized = $request->get('serialized');
        $process = static function ($objects, $parent = null) use ($objectManager, $objectRepository, &$process) : void {
            $pos = 0;
            foreach ($objects as $object) {
                /** @var TreeTrait $entity */
                $entity = $objectRepository->find($object['id']);
                if (method_exists($entity, 'setParent')) {
                    $entity->setParent($parent);
                }

                if (method_exists($entity, 'setPosition')) {
                    $entity->setPosition($pos++);
                }

                //$entity->setParent($parent);
                $objectManager->persist($entity);
                if (isset($object['children'])) {
                    $process($object['children'], $entity);
                }
            }
        };

        $process($serialized);
        $objectManager->flush();
    }

    private function getRootTerms(ManagerRegistry $managerRegistry, $vocabulary, null $organization, $isAdmin=null)
    {
        $class = $vocabulary::class;
        $objectRepository = $managerRegistry->getManager()->getRepository($class);

        if ($objectRepository instanceof NestedTreeRepository) {
            $qb = $objectRepository->getRootNodesQueryBuilder('position');
        } else {
            $qb = $objectRepository->createQueryBuilder('node');
            $qb->orderBy('node.'.$vocabulary::orderBy(), 'ASC');
        }

        if ($vocabulary->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && !$isAdmin) {
            if ($organization) {
                $qb->where('node.organization = :organization')
                    ->setParameter('organization', $organization)
                    ->orWhere('node.organization is null');
            } else {
                $qb->where('node.organization is null');
            }
        }

        return $qb->getQuery()->getResult();
    }

    private function getVocabulariesList(VocabularyRegistry $vocabularyRegistry): array
    {
        $vocsGroups = $vocabularyRegistry->getGroups();
        $userOrg = $this->getUser()->getOrganization();

        //getting vocabularies list, grouped by vocabularies groups
        $vocNames = [];
        foreach ($vocsGroups as $vocGroup) {
            foreach ($vocGroup as $vid => $voc) {
                if ($voc->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && !empty($userOrg)) {
                    $voc->setOrganization($userOrg);
                }

                if ($this->isGranted('VIEW', $voc)) {
                    $label = $vocabularyRegistry->getVocabularyLabel($vid);
                    $voc->setVocabularyLabel($label);
                    $vocNames[] = ['id' => $vid, 'vocabulary' => $voc, 'name' => $voc->getVocabularyLabel(), 'scope' => $voc->getVocabularyStatus()];
                }
            }
        }


        //ordering list
        usort($vocNames, static fn($a, $b): bool => $a['vocabulary']->getVocabularyLabel() > $b['vocabulary']->getVocabularyLabel());

        return $vocNames;
    }

 #[Route(path: '/download/template/{id}', name: 'taxonomy.download.template', requirements: ['id' => '\d+'], methods: 'GET')]
 public function downloadTemplate(Publiposttemplate $publiposttemplate): \Symfony\Component\HttpFoundation\BinaryFileResponse
	{
		$file = $publiposttemplate->getFile();
		if (!$file) {
			throw $this->createNotFoundException(sprintf('No file found for template "%s".', $publiposttemplate->getName()));
		}
        if (!$this->isGranted('VIEW', VocabularyInterface::class)) {
            // Si l'utilisateur n'a pas la permission, on lance une exception d'accès refusé
            throw new AccessDeniedException('Vous n\'avez pas les droits nécessaires pour voir ce vocabulaire.');
        }

		$binaryFileResponse = new BinaryFileResponse($file);
		$binaryFileResponse->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $publiposttemplate->getFileName());

		return $binaryFileResponse;
	}

    #[Route(path: '/get_terms/{vocabularyId}', name: 'taxonomy.get', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function getTerms(ManagerRegistry $managerRegistry, VocabularyRegistry $vocabularyRegistry, $vocabularyId)
    {
        /*
         * @var AbstractTerm
         */
        $vocabulary = $vocabularyRegistry->getVocabularyById($vocabularyId);
        if (!$vocabulary) {
            throw new \InvalidArgumentException('This vocabulary does not exists.');
        }

        $userOrg = $this->getUser()->getOrganization();
        $isAdmin = $this->getUser()->isAdmin();

        return $this->getRootTerms($managerRegistry, $vocabulary, $userOrg, $isAdmin);
    }
}
