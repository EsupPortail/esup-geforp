<?php

namespace App\Vocabulary;

use App\Entity\Term\Actiontype;
use App\Entity\Term\Domain;
use App\Entity\Term\Emailtemplate;
use App\Entity\Term\Evaluationcriterion;
use App\Entity\Term\ImageFile;
use App\Entity\Term\Inscriptionstatus;
use App\Entity\Term\MenuItem;
use App\Entity\Term\Presencestatus;
use App\Entity\Term\Publictype;
use App\Entity\Term\Publiposttemplate;
use App\Entity\Term\Sessiontype;
use App\Entity\Term\Supervisor;
use App\Entity\Term\Tag;
use App\Entity\Term\Theme;
use App\Entity\Term\Title;
use App\Entity\Term\Trainertype;
use App\Entity\Term\Trainingcategory;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class VocabularyRegistry.
 */
final class VocabularyRegistry
{
    private array $vocabularies = [];

    private array $groups = [];

    private array $labels = [];

    /**
     *
     */
    public function __construct()
    {
        // Construction de la liste des vocabulaires 'en dur'
        $i=0;
        $voc = new Title();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Publiposttemplate();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Evaluationcriterion();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Actiontype();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Trainingcategory();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Emailtemplate();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new MenuItem();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Supervisor();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Theme();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Inscriptionstatus();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Presencestatus();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Tag();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Sessiontype();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Trainertype();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Publictype();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new Domain();
        $this->addVocabulary($voc, $i);
        ++$i;
        $voc = new ImageFile();
        $this->addVocabulary($voc, $i);
    }

    public function addVocabulary($vocabulary, $id, $group = 'Misc', $label = null): void
    {


        $vocabulary->setVocabularyId($id);
        $this->vocabularies[$id] = $vocabulary;

        if ($label) {
            $this->labels[$id] = $label;
        }

        if (empty($this->groups[$group])) {
            $this->groups[$group] = [];
        }

        $this->groups[$group][$id] = $vocabulary;
    }


    /**
     * @param string $id
     *
     * @return string|null
     */
    public function getVocabularyById(string $id): ?VocabularyInterface

    {
        return $this->VocabularyInterface[$id] ?? null;
    }

    /**
     * @param string $id
     *
     * @return VocabularyInterface
     */
    public function getVocabularyLabel($id)
    {
        return $this->labels[$id] ?? null;
    }

    public function getVocabularies(): array
    {
        return $this->vocabularies;
    }

    /**
     * returns known groups.
     *
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * Counts and returns the number of usages of term among all entities
     *
     * @param $vocTerm
     * @param bool $getCount
     */
    public function getTermUsages(EntityManager $entityManager, $vocTerm, $getCount = true): array|int
    {
        /* @var ObjectRepository $repo */
        $meta     = $entityManager->getMetadataFactory()->getAllMetadata();
        $vocClass = $vocTerm::class;
        $termId   = $vocTerm->getId();

        $usages = [];

        $totalCount = 0;

        foreach ($meta as $metum) {
            $mapps = $metum->getAssociationMappings();
            foreach ($mapps as $mapp) {
                if ($vocClass !== $mapp['targetEntity']) {
                    continue;
                }
                if (!$mapp['isOwningSide']) {
                    continue;
                }
                if ( $mapp['type'] === ClassMetadataInfo::MANY_TO_MANY ) {
                    //getting all entities
                    $qb1 = $entityManager->createQueryBuilder();
                    $qb2 = $entityManager->createQueryBuilder();
                    $qb1->select('f.id')
                        ->from($metum->getName(), 'f')
                        ->leftJoin('f.' . $mapp['fieldName'], 'c')
                        ->where( $qb1->expr()->in('c', ':c'))
                        ->setParameter('c', $vocTerm);

                    $qb2->select('t')
                        ->from($metum->getName(), 't')
                        ->where( $qb1->expr()->in('t.id', ':ids'))->setParameter('ids', $qb1->getQuery()->getResult());

                    $tmpArray = $qb2->getQuery()->getResult();

                    if ((is_countable($tmpArray) ? count($tmpArray) : 0) !== 0) {
                        $totalCount += is_countable($tmpArray) ? count($tmpArray) : 0;
                        $usages[$metum->getName()] = ['multiple' => true, 'fieldName' => $mapp['fieldName'], 'entities' => $tmpArray];
                    }
                }
                else {
                    $qb = $entityManager->createQueryBuilder()
                        ->select('t')
                        ->from($metum->getName(), 't')
                        ->where('t.' . $mapp['fieldName'] . '= :id')->setParameter('id', $termId);

                    $tmpArray = $qb->getQuery()->getResult();
                    if ((is_countable($tmpArray) ? count($tmpArray) : 0) !== 0) {
                        $totalCount += is_countable($tmpArray) ? count($tmpArray) : 0;
                        $usages[$metum->getName()] = ['multiple' => false, 'fieldName' => $mapp['fieldName'], 'entities' => $tmpArray];
                    }
                }
            }
        }

        if ($getCount) {
            return $totalCount;
        }

        return $usages;
    }

    /**
     * Replaces the a term by another in all its usages.
     *
     * @param EntityManager $entityManager
     * @param $vocTermFrom
     * @param $vocTermTo
     * @throws NotSupported
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function replaceTermInUsages(EntityManager $entityManager, $vocTermFrom, $vocTermTo): void
    {
        $usages       = $this->getTermUsages($entityManager, $vocTermFrom, $count = false);
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($usages as $class => $classUsage) {
            foreach ($classUsage['entities'] as $entity) {
                $ent = $entityManager->getRepository($class)->findBy(['id' => $entity->getId()]);
                $ent = $ent[0];
                $value = $propertyAccessor->getValue($ent, $classUsage['fieldName']);

                $vocClass = $vocTermTo::class;
                if ($value instanceof $vocClass) {

                    $propertyAccessor->setValue($entity, $classUsage['fieldName'], $vocTermTo);
                }
                else {

                    $termInCollection = $this->checkTermIsInCollection($vocTermTo, $value);
                    if (is_array($value)) {
                        $valueCount = count($value);
                        for ($pos = 0; $pos < $valueCount; ++$pos) {
                            if (method_exists($value[$pos], 'getId') && ($value[$pos]->getId() === $vocTermFrom->getId())) {
                                //if destination element is not already present in collection, we can do a replacement
                                if ($termInCollection) {
                                    $value = array_splice($value, $pos);
                                    break;
                                } else {
                                    $value[$pos] = $vocTermTo;
                                }
                            }
                        }
                        
                        $propertyAccessor->setValue($entity, $classUsage['fieldName'], $value);
                    } elseif ($value instanceof \Traversable) {
                        foreach ($value as $key => $val) {
                            if (!method_exists($val, 'getId')) {
                                continue;
                            }
                            if ($val->getId() !== $vocTermFrom->getId()) {
                                continue;
                            }
                            if ($termInCollection) {
                                $value->remove($key);
                                break;
                            }
                            else {
                                $value->offsetSet($key, $vocTermTo);
                            }
                        }
                        
                        $propertyAccessor->setValue($entity, $classUsage['fieldName'], $value);
                    }
                }
            }
        }

        $entityManager->flush();
    }

    /**
     * @param $term
     * @param $collection
     *
     * @return bool
     */
    private function checkTermIsInCollection($term, $collection)
    {
        $isInCollection = false;

        if ($collection instanceof \Traversable) {
            $collection = $collection->toArray();
        }

        foreach ($collection as $val) {
            if (method_exists($val, 'getId') && ($val->getId() === $term->getId())) {
                $isInCollection = true;
            }
        }

        return $isInCollection;
    }
}
