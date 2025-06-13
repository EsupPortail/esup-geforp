<?php

namespace App\Form\DataTransformer;

use App\Entity\AccessRight;
use App\Form\Type\AccessRightType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class AccessRightToEntityTransformer implements DataTransformerInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function transform($value): array
    {
        // From entities to IDs
        if (null === $value) {
            return [];
        }

        return array_map(fn($right) => $right->getId(), $value);
    }

    public function reverseTransform($value): array
    {
        // From IDs to entities
        if (!is_array($value)) {
            return [];
        }

        $accessRights = [];
        foreach ($value as $id) {
            $right = $this->em->getRepository(AccessRightType::class)->find($id);
            if (!$right) {
                throw new TransformationFailedException(sprintf('Le droit [%s] est introuvable.', $id));
            }
            $accessRights[] = $right;
        }

        return $accessRights;
    }
}