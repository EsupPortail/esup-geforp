<?php

namespace App\Serializer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class CircularReferenceHandler
{
    public function handle($object, string $format = null, array $context = [])
    {
        return $object->getId();
    }
}