<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/14/16
 * Time: 5:33 PM
 */

namespace App\Entity;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Alert;

class SingleAlert
{
    /* @var \App\Entity\Alert */
    public $alert;

    public $session_id;

    public $trainee_id;

    public function getAlert()
    {
        return $this->alert;
    }

    public function setAlert(Alert $alert)
    {
        $this->alert = $alert;
    }

    public function getSessionId()
    {
        return $this->session_id;
    }

    public function setSessionId($session_id)
    {
        $this->session_id = $session_id;
    }

    public function getTraineeId()
    {
        return $this->trainee_id;
    }

    public function setTraineeId($trainee_id)
    {
        $this->trainee_id = $trainee_id;
    }

}