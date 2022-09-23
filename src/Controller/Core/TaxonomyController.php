<?php

namespace App\Controller\Core;

use App\Entity\Core\Term\Actiontype;
use App\Entity\Core\Term\Emailtemplate;
use App\Entity\Core\Term\Evaluationcriterion;
use App\Entity\Core\Term\Inscriptionstatus;
use App\Entity\Core\Term\MenuItem;
use App\Entity\Core\Term\Presencestatus;
use App\Entity\Core\Term\Publictype;
use App\Entity\Core\Term\Sessiontype;
use App\Entity\Core\Term\Supervisor;
use App\Entity\Core\Term\Tag;
use App\Entity\Core\Term\Theme;
use App\Entity\Core\Term\Title;
use App\Entity\Core\Term\Trainertype;
use App\Entity\Core\Term\Trainingcategory;
use App\Entity\Organization;
use App\Vocabulary\VocabularyRegistry;
use Doctrine\ORM\EntityNotFoundException;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Core\AbstractOrganization;
use App\Entity\Core\Term\AbstractTerm;
use App\Entity\Core\Term\Publiposttemplate;
use App\Entity\Core\Term\TreeTrait;
use App\Form\Type\VocabularyType;
use App\Entity\Core\Term\VocabularyInterface;
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
 * @Route("/admin/taxonomy")
 */
class TaxonomyController extends AbstractController
{
    /**
     * @Route("/", name="taxonomy.index")
     */
    public function indexAction(ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry)
    {
/*        if (!$this->get('security.context')->isGranted('VIEW', VocabularyInterface::class)) {
            throw new AccessDeniedException();
        } */

        return $this->render('Core/views/Taxonomy/index.html.twig', array(
            'vocabularies' => $this->getVocabulariesList($doctrine,  $vocRegistry),
        ));
    }

    /**
     * @param AbstractTerm         $term
     * @param AbstractOrganization $organization
     *
     * @Route("/{vocabularyId}/view/{organizationId}", name="taxonomy.view", defaults={"organizationId" = null})
     * @ParamConverter("organization", class="App\Entity\Core\AbstractOrganization", options={"id" = "organizationId"}, isOptional="true")
     *
     * @throws EntityNotFoundException
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function viewVocabularyAction(ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, $vocabularyId, $organization = null)
    {
        /** @var AbstractTerm $abstractVocabulary */
        $abstractVocabulary = $vocRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);
        $userAccessRights = $this->getUser()->getAccessRights();
        $userVocabularyAccessRights = array(
            'nationalEdit' => in_array('sygefor_core.access_right.vocabulary.national', $userAccessRights),
            'localEdit' => in_array('sygefor_core.access_right.vocabulary.own', $userAccessRights),
            'allView' => in_array('sygefor_core.access_right.vocabulary.view.all', $userAccessRights),
            'allEdit' => in_array('sygefor_core.access_right.vocabulary.all', $userAccessRights),
        );

        if (!$organization) {
            $org = $this->getUser()->getOrganization();
            $redirectUrl = $this->redirect($this->generateUrl('taxonomy.view', array('vocabularyId' => $vocabularyId, 'organizationId' => $org->getId())));
            if ($abstractVocabulary->getVocabularyStatus() === VocabularyInterface::VOCABULARY_LOCAL) {
                return $redirectUrl;
            } elseif ($abstractVocabulary->getVocabularyStatus() === VocabularyInterface::VOCABULARY_MIXED && !$userVocabularyAccessRights['allView'] && !$userVocabularyAccessRights['nationalEdit']) {
                return $redirectUrl;
            }
        }

        // set organization to abstract vocabulary to check access rights
        $abstractVocabulary->setOrganization($organization);
        if (!$this->isGranted('VIEW', $abstractVocabulary)) {
            throw new AccessDeniedException();
        }

        // needed for template organization tabs
        $organizations = array();
        if ($abstractVocabulary->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL) {
            $alterOrganizations = $doctrine->getManager()->getRepository(AbstractOrganization::class)->findAll();
            $alterAbstractVocabulary = $vocRegistry->getVocabularyById($vocabularyId);
            foreach ($alterOrganizations as $alterOrganization) {
                $alterAbstractVocabulary->setOrganization($alterOrganization);
                if ($this->isGranted('EDIT', $alterAbstractVocabulary) || $this->isGranted('VIEW', $alterAbstractVocabulary)) {
                    $organizations[$alterOrganization->getId()] = $alterOrganization;
                }
            }
        }

        $terms = $this->getRootTerms($doctrine, $abstractVocabulary, $organization);
        if ($organization) {
            foreach ($terms as $key => $term) {
                if (!$term->getOrganization()) {
                    unset($terms[$key]);
                }
            }
        }

        return $this->render('Core/views/Taxonomy/view.html.twig', array(
            'organization' => $organization,
            'organizations' => $organizations,
            'userVocabularyAccessRights' => true, //$userVocabularyAccessRights,
            'terms' => $terms,
            'vocabulary' => $abstractVocabulary,
            'vocabularies' => $this->getVocabulariesList($doctrine,  $vocRegistry),
            'sortable' => $abstractVocabulary::orderBy() === 'position',
            'depth' => method_exists($abstractVocabulary, 'getChildren') ? 2 : 1,
        ));
    }

    /**
     * @Route("/{vocabularyId}/edit/{id}/{organizationId}", name="taxonomy.edit", defaults={"id" = null, "organizationId" = null})
     */
    public function editVocabularyTermAction(Request $request, ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, $vocabularyId, $organizationId, $id = null)
    {
        $organization = null;
        if ($organizationId) {
            $organization = $doctrine->getManager()->getRepository(AbstractOrganization::class)->find($organizationId);
        }
        $term = null;
        $abstractVocabulary = $vocRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);
        $termClass = get_class($abstractVocabulary);
        $em = $doctrine->getManager();

        // find term
        if ($id) {
            $term = $em->find($termClass, $id);
        }

        // create term if not found
        if (!$term) {
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
                $em->persist($term);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le terme a bien été enregistré.');

                $organization_id = null;
                if ($organization) {
                    $organization_id = $organization->getId();
                }

                return $this->redirect($this->generateUrl('taxonomy.view', array('vocabularyId' => $vocabularyId, 'organizationId' => $organization_id)));
            }
        }

        return $this->render('Core/views/Taxonomy/edit.html.twig', array(
            'vocabulary' => $abstractVocabulary,
            'organization' => $organization,
            'term' => $term,
            'id' => $id,
            'form' => $form->createView(),
            'vocabularies' => $this->getVocabulariesList($doctrine,  $vocRegistry),
        ));
    }

    /**
     * @Route("/{vocabularyId}/remove/{id}", name="taxonomy.remove")
     */
    public function removeAction(Request $request, ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, $vocabularyId, $id)
    {
        $abstractVocabulary = $vocRegistry->getVocabularyById($vocabularyId);
        $abstractVocabulary->setVocabularyId($vocabularyId);
        $termClass = get_class($abstractVocabulary);
        $em = $doctrine->getManager();

        // find term
        $term = $em->find($termClass, $id);
        if (!$term) {
            throw new NotFoundHttpException();
        }

        // protected term because needed for special system operations
        if ($term->isLocked()) {
            throw new AccessDeniedException("This term can't be removed");
        }

        if (!$this->isGranted('REMOVE', $term)) {
            throw new AccessDeniedException();
        }

        // get term usage
        $count = $vocRegistry->getTermUsages($em, $term);

        $formB = $this->createFormBuilder(null, array('validation_groups' => array('taxonomy_term_remove')));
        $constraint = new NotBlank(array('message' => 'Vous devez sélectionner un terme de substitution'));
        $constraint->addImplicitGroupName('taxonomy_term_remove');

        // build query
        $queryBuilder = $em->createQueryBuilder('s')
            ->select('t')
            ->from($termClass, 't')
            ->where('t.id != :id')->setParameter('id', $id)
            ->orderBy('t.'.$abstractVocabulary::orderBy());
        if ($term->getOrganization()) {
            $queryBuilder
                ->andWhere('t.organization = :organization')
                ->setParameter('organization', $term->getOrganization());
        }
        $queryBuilder->orWhere('t.organization is null');

        //if entities are linked to current
        if ($count > 0) {
            $required = !empty($abstractVocabulary::$replacementRequired);
            $formB
                ->add('term', 'entity',
                    array(
                        'class' => $termClass,
                        'expanded' => true,
                        'label' => 'Terme de substitution',
                        'required' => $required,
                        'constraints' => $required ? $constraint : null,
                        'query_builder' => $queryBuilder,
                        'empty_value' => $required ? null : '- Aucun -',
                    )
                );
        }

        $organization_id = null;
        if ($term->getOrganization()) {
            $organization_id = $term->getOrganization()->getId();
        }

        $form = $formB->getForm();
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($form->has('term')) {
                    $newTerm = $form->get('term')->getData();
                    if ($newTerm) {
                        $vocRegistry->replaceTermInUsages(
                            $em,
                            $term,
                            $newTerm);
                    }
                }
                $em->remove($term);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', 'Le terme a bien été supprimé.');

                return $this->redirect($this->generateUrl('taxonomy.view', array('vocabularyId' => $vocabularyId, 'organizationId' => $organization_id)));
            }
        }

        return $this->render('Core/views/Taxonomy/remove.html.twig', array(
            'vocabulary' => $abstractVocabulary,
            'organization' => $term->getOrganization(),
            'organization_id' => $organization_id,
            'term' => $term,
            'vocabularies' => $this->getVocabulariesList($doctrine, $vocRegistry),
            'count' => $count,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/{vocabulary}/terms/order", name="taxonomy.terms_order", options={"expose"=true}, defaults={"_format" = "json"})
     * @Method({"POST"})
     * @Rest\View
     */
    public function termsOrderAction(ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, $vocabulary, Request $request)
    {
        $abstractVocabulary = $$vocRegistry->getVocabularyById($vocabulary);
        $abstractVocabulary->setVocabularyId($vocabulary);
        $termClass = get_class($abstractVocabulary);

        $em = $doctrine->getManager();
        $repository = $em->getRepository($termClass);
        $serialized = $request->get('serialized');
        $process = function ($objects, $parent = null) use ($em, $repository, &$process) {
            $pos = 0;
            foreach ($objects as $object) {
                /** @var TreeTrait $entity */
                $entity = $repository->find($object['id']);
                if (method_exists($entity, 'setParent')) {
                    $entity->setParent($parent);
                }
                if (method_exists($entity, 'setPosition')) {
                    $entity->setPosition($pos++);
                }
                //$entity->setParent($parent);
                $em->persist($entity);
                if (isset($object['children'])) {
                    $process($object['children'], $entity);
                }
            }
        };

        $process($serialized);
        $em->flush();
    }

    /**
     * Return the terms for a specified vocabulary, filter by an organization
     * For tree vocabulary, only root ones.
     *
     * @param $vocabulary
     * @param null $organization
     *
     * @return mixed
     */
    private function getRootTerms(ManagerRegistry $doctrine, $vocabulary, $organization)
    {
        $class = get_class($vocabulary);
        $repository = $doctrine->getManager()->getRepository($class);

        if ($repository instanceof NestedTreeRepository) {
            $qb = $repository->getRootNodesQueryBuilder('position');
        } else {
            $qb = $repository->createQueryBuilder('node');
            $qb->orderBy('node.'.$vocabulary::orderBy(), 'ASC');
        }

        if ($vocabulary->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL) {
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

    /**
     * @return array list of allowed vocabularies, grouped by existing groups
     */
    private function getVocabulariesList(ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry)
    {
        $vocsGroups = $vocRegistry->getGroups();
        $userOrg = $this->getUser()->getOrganization();

        //getting vocabularies list, grouped by vocabularies groups
        $vocNames = array();
        foreach ($vocsGroups as $group => $vocs) {
            foreach ($vocs as $vid => $voc) {
                if ($voc->getVocabularyStatus() !== VocabularyInterface::VOCABULARY_NATIONAL && !empty($userOrg)) {
                    $voc->setOrganization($userOrg);
                }

                if ($this->isGranted('VIEW', $voc)) {
                    $label = $vocRegistry->getVocabularyLabel($vid);
                    $voc->setVocabularyLabel($label);
                    $vocNames[] = array(
                        'id' => $vid,
                        'vocabulary' => $voc,
                        'name' => $voc->getVocabularyName(),
                        'scope' => $voc->getVocabularyStatus(),
                        'canEdit' => $this->isGranted('EDIT', $voc)
                    );
                }
            }
        }


        //ordering list
        usort($vocNames, function ($a, $b) {
            return $a['vocabulary']->getVocabularyLabel() > $b['vocabulary']->getVocabularyLabel();
        });

        return $vocNames;
    }

	/**
	 * @Route(
	 *     "/download/template/{id}",
	 *     name="taxonomy.download.template",
	 *     requirements={"id"="\d+"}
	 * )
	 * @Method("GET")
	 * @Security("is_granted('VIEW', 'Sygefor\\Bundle\\TaxonomyBundle\\Vocabulary\\VocabularyInterface')")
	 *
	 * @param Publiposttemplate $template
	 *
	 * @return BinaryFileResponse
	 */
	public function downloadTemplateAction(Publiposttemplate $template)
	{
		$file = $template->getFile();
		if (!$file) {
			throw $this->createNotFoundException("No file found for template \"{$template->getName()}\".");
		}

		$response = new BinaryFileResponse($file);
		$response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $template->getFileName());

		return $response;
	}

    /**
     * @Route("/get_terms/{vocabularyId}", name="taxonomy.get", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     */
    public function getTermsAction(ManagerRegistry $doctrine, VocabularyRegistry $vocRegistry, $vocabularyId)
    {
        /*
         * @var AbstractTerm
         */
        $vocabulary = $vocRegistry->getVocabularyById($vocabularyId);
        if (!$vocabulary) {
            throw new \InvalidArgumentException('This vocabulary does not exists.');
        }

        $userOrg = $this->getUser()->getOrganization();
        $terms = $this->getRootTerms($doctrine, $vocabulary, $userOrg);

        return $terms;
    }
}
