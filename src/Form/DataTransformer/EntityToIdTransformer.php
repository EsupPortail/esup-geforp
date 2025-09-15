<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 25/06/14
 * Time: 15:02.
 */

namespace App\Form\DataTransformer;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class ObjectToIdTransformer.
 */
final class EntityToIdTransformer implements DataTransformerInterface
{
    private $entityClass;


    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * @param $entity
     *
     * @throws TransformationFailedException
     *
     * @return mixed
     */
    public function transform($entity)
    {
        if (null === $entity) {
            return;
        }
        if ('' === $entity) {
            return;
        }
        return $entity->getId();
    }

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * This method is called when {@link Form::submit()} is called to transform the requests tainted data
     * into an acceptable format for your data processing/model layer.
     *
     * This method must be able to deal with empty values. Usually this will
     * be an empty string, but depending on your implementation other empty
     * values are possible as well (such as empty strings). The reasoning behind
     * this is that value transformers must be chainable. If the
     * reverseTransform() method of the first value transformer outputs an
     * empty string, the second value transformer must be able to process that
     * value.
     *
     * By convention, reverseTransform() should return NULL if an empty string
     * is passed.
     *
     * @param mixed $id The value in the transformed representation
     *
     * @throws TransformationFailedException when the transformation fails
     *
     * @return mixed The value in the original representation
     */
    public function reverseTransform($id)
    {
        if (!$id) {
            return;
        }

        $object = $this->entityManager->getRepository($this->entityClass)->find($id);

        if (null === $object) {
            throw new TransformationFailedException(sprintf(
                'An instance of "%s" with id "%s" does not exist!',
                $this->entityClass,
                $id
            )); // return null;
        }

        return $object;
    }

    /**
     * @param $entityClass
     */
    public function setEntityClass($entityClass): void
    {
        $this->entityClass = $entityClass;
    }
}
