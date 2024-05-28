<?php

namespace App\BatchOperations\Training;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use App\BatchOperations\AbstractBatchOperation;
use App\Utils\Search\SearchService;
use App\Model\SemesteredTraining;
use App\Entity\Core\AbstractTraining;
use App\Utils\TrainingTypeRegistry;
use Symfony\Component\Security\Core\Security;

class ConvertTypeBatchOperation extends AbstractBatchOperation
{
    /** @var EntityManager $security */
    protected $security;

    /** @var TrainingTypeRegistry $trainingTypeRegistry | get new entity type class */
    protected $trainingTypeRegistry;

    /** @var array $correspondanceBetweenTrainings */
    protected $correspondanceBetweenTrainings = array();

    /** @var array $clonedTrainingNumbers */
    protected $clonedTrainingNumbers = array();

    /** @var SearchService $semesteredTrainingSearch */
    protected $semesteredTrainingSearch;

    /** @var Type $semesteredTrainingType */
    protected $semesteredTrainingType;

    /**
     * ConvertTypeBatchOperation constructor.
     *
     * @param Security              $security
     * @param TrainingTypeRegistry $trainingTypeRegistry
     * @param SearchService        $semesteredTrainingSearch
     * @param Type                 $semesteredTrainingType
     */
    public function __construct(Security $securityContext, TrainingTypeRegistry $trainingTypeRegistry,
                                SearchService $semesteredTrainingSearch, Type $semesteredTrainingType)
    {
        $this->security = $securityContext;
        $this->trainingTypeRegistry = $trainingTypeRegistry;
        $this->semesteredTrainingSearch = $semesteredTrainingSearch;
        $this->semesteredTrainingType = $semesteredTrainingType;
    }

    /**
     * @param array $idList
     * @param array $options
     *
     * @return mixed
     */
    public function execute(array $idList = array(), array $options = array())
    {
        $type = $options[0]['type'];
        // get trainings from semestered trainings and verify if there are not several times the same training
        // not transform same training type and meetings
        $entities = SemesteredTraining::getTrainingsByIds($idList, $this->doctrine->getManager(), array($type, 'meeting'));

        // first create new entities and get old entity sessions
        foreach ($entities as $key => $entity) {
            if ($this->security->isGranted('EDIT', $entity)) {
                // create new entity and copy common old entity properties
                $this->createAndCopyEntity($entity, $type, $this->doctrine->getManager(), $key);

                // transfer sessions to new training
                $clonedTrainingSessions = new ArrayCollection();
                foreach ($entity->getSessions() as $session) {
                    $session->setTraining($this->correspondanceBetweenTrainings[$entity->getId()]);
                    $clonedTrainingSessions->add($session);
                }
                // remove sessions for old entity
                $entitySessions = new ArrayCollection();
                $entity->setSessions($entitySessions);

                // set sessions to new entity
                $this->correspondanceBetweenTrainings[$entity->getId()]->setSessions($clonedTrainingSessions);
            }
        }
        $this->doctrine->getManager()->flush();

        // then remove old entities
        $entityRemovedIds = array();
        foreach ($entities as $entity) {
            if ($this->security->isGranted('EDIT', $entity)) {
                $entityRemovedIds[] = $entity->getId();
                $this->doctrine->getManager()->remove($entity);
            }
        }
        $this->doctrine->getManager()->flush();
        // then reattributes old entities number to new ones
        foreach ($this->clonedTrainingNumbers as $values) {
            $values['entity']->setNumber($values['number']);
        }
        $this->doctrine->getManager()->flush();

        // remove cascade semestered training
        // some of them are not found by elastica because the semestered training could not have the same id because of session moved from old trainings to new one
        if (!empty($entityRemovedIds)) {
            // search wrong existing documents
            $trainingIdFilter = new Terms('training.id', $entityRemovedIds);
            $this->semesteredTrainingSearch->addFilter('training.id', $trainingIdFilter);
            $this->semesteredTrainingSearch->setSize(9999);
            $result = $this->semesteredTrainingSearch->search();

            // delete them
/*            if (!empty($result['items'])) {
                $bulk = new Bulk($this->elasticaClient);
                $bulk->setIndex($this->elasticaIndex);
                $bulk->setType($this->semesteredTrainingType);
                foreach ($result['items'] as $semesteredTraining) {
                    $action = new Action(Action::OP_TYPE_DELETE);
                    $action->setId($semesteredTraining['id']);
                    $bulk->addAction($action);
                }

                return $bulk->send();
            }*/
        }
    }

    /**
     * @param AbstractTraining $training
     * @param string           $type
     * @param EntityManager    $em
     * @param int              $key
     */
    protected function createAndCopyEntity(AbstractTraining $training, $type, EntityManager $em, $key)
    {
        // get database max number for organization
        $query = $em->createQuery('SELECT MAX(t.number) FROM App\Entity\Core\AbstractTraining t WHERE t.organization = :organization')
            ->setParameter('organization', $training->getOrganization());
        $max = (int) $query->getSingleScalarResult();

        // create and copy
        $typeClass = $this->trainingTypeRegistry->getType($type);
        /** @var AbstractTraining $cloned */
        $cloned = new $typeClass['class']();
        $cloned->copyProperties($training);

        // set max number + entity array key because max number is always the same till we do not flush
        $cloned->setNumber($max + $key + 1);
        $em->persist($cloned);

        // copy array collection elements
        $this->mergeArrayCollectionsAndFlush($cloned, $training);

        // some flags for following operations
        $this->correspondanceBetweenTrainings[$training->getId()] = $cloned;
        $this->clonedTrainingNumbers[] = array('entity' => $cloned, 'number' => $training->getNumber());
    }

    /**
     * @param AbstractTraining $dest
     * @param AbstractTraining $source
     */
    protected function mergeArrayCollectionsAndFlush($dest, $source)
    {
        // clone duplicate materials
        $tmpMaterials = $source->getMaterials();
        if (!empty($tmpMaterials)) {
            foreach ($tmpMaterials as $material) {
                $newMat = clone $material;
                $dest->addMaterial($newMat);
            }
        }
    }
}
