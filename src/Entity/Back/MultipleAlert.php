<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/14/16
 * Time: 5:33 PM
 */

namespace App\Entity\Back;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;

class MultipleAlert
{

    protected $alerts;

    public function __construct()
    {
        $this->alerts = new ArrayCollection();
    }

    public function getAlerts()
    {
        return $this->alerts;
    }

}