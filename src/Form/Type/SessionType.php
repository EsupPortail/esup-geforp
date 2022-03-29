<?php

namespace App\Form\Type;

use Doctrine\ORM\EntityRepository;
use App\Entity\Organization;
use App\Entity\Core\AbstractInstitution;
use App\Entity\Core\Term\Supervisor;
use App\Entity\Core\Term\Tag;
use App\Entity\Core\Term\Theme;
use App\Entity\Core\Term\TrainingCategory;
use App\Entity\Core\AbstractTraining;
use App\Security\AccessRight\AccessRightRegistry;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class TrainingType.
 */
class SessionType extends AbstractSessionType
{

}
