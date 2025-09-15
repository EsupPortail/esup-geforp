<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CircularReferenceHandler
{
    public function __invoke($object)
    {
        return $object->getId();
    }
}