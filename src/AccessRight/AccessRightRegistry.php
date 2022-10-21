<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 14/03/14
 * Time: 16:52.
 */
namespace App\AccessRight;

use App\Entity\Core\User;
// Access right pour training
use App\Security\Authorization\AccessRight\Training\AllTrainingCreateAccessRight;
use App\Security\Authorization\AccessRight\Training\AllTrainingDeleteAccessRight;
use App\Security\Authorization\AccessRight\Training\AllTrainingUpdateAccessRight;
use App\Security\Authorization\AccessRight\Training\AllTrainingViewAccessRight;
use App\Security\Authorization\AccessRight\Training\OwnTrainingCreateAccessRight;
use App\Security\Authorization\AccessRight\Training\OwnTrainingDeleteAccessRight;
use App\Security\Authorization\AccessRight\Training\OwnTrainingUpdateAccessRight;
use App\Security\Authorization\AccessRight\Training\OwnTrainingViewAccessRight;
use App\Security\Authorization\AccessRight\Trainee\AllTraineeCreateAccessRight;
use App\Security\Authorization\AccessRight\Trainee\AllTraineeUpdateAccessRight;
use App\Security\Authorization\AccessRight\Trainee\AllTraineeDeleteAccessRight;
use App\Security\Authorization\AccessRight\Trainee\AllTraineeViewAccessRight;
use App\Security\Authorization\AccessRight\Trainee\OwnTraineeCreateAccessRight;
use App\Security\Authorization\AccessRight\Trainee\OwnTraineeDeleteAccessRight;
use App\Security\Authorization\AccessRight\Trainee\OwnTraineeUpdateAccessRight;
use App\Security\Authorization\AccessRight\Trainee\OwnTraineeViewAccessRight;
use App\Security\Authorization\AccessRight\Inscription\AllInscriptionCreateAccessRight;
use App\Security\Authorization\AccessRight\Inscription\AllInscriptionDeleteAccessRight;
use App\Security\Authorization\AccessRight\Inscription\AllInscriptionUpdateAccessRight;
use App\Security\Authorization\AccessRight\Inscription\AllInscriptionViewAccessRight;
use App\Security\Authorization\AccessRight\Inscription\OwnInscriptionCreateAccessRight;
use App\Security\Authorization\AccessRight\Inscription\OwnInscriptionDeleteAccessRight;
use App\Security\Authorization\AccessRight\Inscription\OwnInscriptionUpdateAccessRight;
use App\Security\Authorization\AccessRight\Inscription\OwnInscriptionViewAccessRight;
use App\Security\Authorization\AccessRight\Institution\AllInstitutionCreateAccessRight;
use App\Security\Authorization\AccessRight\Institution\AllInstitutionDeleteAccessRight;
use App\Security\Authorization\AccessRight\Institution\AllInstitutionUpdateAccessRight;
use App\Security\Authorization\AccessRight\Institution\AllInstitutionViewAccessRight;
use App\Security\Authorization\AccessRight\Institution\OwnInstitutionCreateAccessRight;
use App\Security\Authorization\AccessRight\Institution\OwnInstitutionDeleteAccessRight;
use App\Security\Authorization\AccessRight\Institution\OwnInstitutionUpdateAccessRight;
use App\Security\Authorization\AccessRight\Institution\OwnInstitutionViewAccessRight;
use App\Security\Authorization\AccessRight\Trainer\AllTrainerCreateAccessRight;
use App\Security\Authorization\AccessRight\Trainer\AllTrainerDeleteAccessRight;
use App\Security\Authorization\AccessRight\Trainer\AllTrainerUpdateAccessRight;
use App\Security\Authorization\AccessRight\Trainer\AllTrainerViewAccessRight;
use App\Security\Authorization\AccessRight\Trainer\OwnTrainerCreateAccessRight;
use App\Security\Authorization\AccessRight\Trainer\OwnTrainerDeleteAccessRight;
use App\Security\Authorization\AccessRight\Trainer\OwnTrainerUpdateAccessRight;
use App\Security\Authorization\AccessRight\Trainer\OwnTrainerViewAccessRight;

use App\Security\Authorization\AccessRight\User\AllOrganizationUserAccessRight;
use App\Security\Authorization\AccessRight\User\OwnOrganizationUserAccessRight;
use App\Security\Authorization\AccessRight\Vocabulary\AllOrganizationVocabularyAccessRight;
use App\Security\Authorization\AccessRight\Vocabulary\NationalVocabularyAccessRight;
use App\Security\Authorization\AccessRight\Vocabulary\OwnOrganizationVocabularyAccessRight;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class AccessRightRegistry.
 */
class AccessRightRegistry
{
    /**
     * @var array
     */
    private $rights;

    /**
     * @var array
     */
    private $groups;

    /**
     * @var Security
     */
    private $security;

    const RIGHT_NAMES =  array(
        0 => "sygefor_core.rights.user.own", 1 => "sygefor_core.rights.user.all",
        2 => "sygefor_core.rights.vocabulary.own", 3 => "sygefor_core.rights.vocabulary.national", 4 => "sygefor_core.rights.vocabulary.all",
        5 => 'sygefor_training.rights.training.own.view', 6 => 'sygefor_training.rights.training.own.create', 7=> 'sygefor_training.rights.training.own.update', 8 => 'sygefor_training.rights.training.own.delete',
        9 => 'sygefor_training.rights.training.all.view', 10 => 'sygefor_training.rights.training.all.create', 11 => 'sygefor_training.rights.training.all.update', 12 => 'sygefor_training.rights.training.all.delete',
        13 => 'sygefor_trainee.rights.trainee.own.view', 14 => 'sygefor_trainee.rights.trainee.own.create', 15 => 'sygefor_trainee.rights.trainee.own.update', 16 => 'sygefor_trainee.rights.trainee.own.delete',
        17 => 'sygefor_trainee.rights.trainee.all.view', 18 => 'sygefor_trainee.rights.trainee.all.create', 19 => 'sygefor_trainee.rights.trainee.all.update', 20 => 'sygefor_trainee.rights.trainee.all.delete',
        21 => 'sygefor_inscription.rights.inscription.own.view', 22 => 'sygefor_inscription.rights.inscription.own.create', 23 => 'sygefor_inscription.rights.inscription.own.update', 24 => 'sygefor_inscription.rights.inscription.own.delete',
        25 => 'sygefor_inscription.rights.inscription.all.view', 26 => 'sygefor_inscription.rights.inscription.all.create', 27 => 'sygefor_inscription.rights.inscription.all.update', 28 => 'sygefor_inscription.rights.inscription.all.delete',
        29 => 'sygefor_institution.rights.institution.own.view', 30 => 'sygefor_institution.rights.institution.own.create', 31 => 'sygefor_institution.rights.institution.own.update', 32 => 'sygefor_institution.rights.institution.own.delete',
        33 => 'sygefor_institution.rights.institution.all.view', 34 => 'sygefor_institution.rights.institution.all.create', 35 => 'sygefor_institution.rights.institution.all.update', 36 => 'sygefor_institution.rights.institution.all.delete',
        37 => 'sygefor_trainer.rights.trainer.own.view', 38 => 'sygefor_trainer.rights.trainer.own.create', 39 => 'sygefor_trainer.rights.trainer.own.update', 40 => 'sygefor_trainer.rights.trainer.own.delete',
        41 => 'sygefor_trainer.rights.trainer.all.view', 42 => 'sygefor_trainer.rights.trainer.all.create', 43 => 'sygefor_trainer.rights.trainer.all.update', 44 => 'sygefor_trainer.rights.trainer.all.delete'
    );


    /**
     * class constructor.
     */
    public function __construct(Security $security)
    {
        $this->rights = array();
        $this->groups = array();
        $this->security = $security;

        // Construction de la liste des access right 'en dur'
        $i=0;
        $group = "Utilisateurs";
        $accessRight = new OwnOrganizationUserAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllOrganizationUserAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Vocabulaires";
        $accessRight = new OwnOrganizationVocabularyAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new NationalVocabularyAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllOrganizationVocabularyAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Formations";
        $accessRight = new OwnTrainingViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainingCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainingUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainingDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainingViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainingCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainingUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainingDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Stagiaires";
        $accessRight = new OwnTraineeViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTraineeCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTraineeUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTraineeDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTraineeViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTraineeCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTraineeUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTraineeDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Inscriptions";
        $accessRight = new OwnInscriptionViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInscriptionCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInscriptionUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInscriptionDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInscriptionViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInscriptionCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInscriptionUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInscriptionDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Etablissements";
        $accessRight = new OwnInstitutionViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInstitutionCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInstitutionUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnInstitutionDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInstitutionViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInstitutionCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInstitutionUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllInstitutionDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $group = "Formateurs";
        $accessRight = new OwnTrainerViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainerCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainerUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new OwnTrainerDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainerViewAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainerCreateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainerUpdateAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
        $accessRight = new AllTrainerDeleteAccessRight();
        $this->addAccessRight($i, $accessRight, $group);
        $i++;
    }

    /**
     * Add right.
     *
     * @param $id
     * @param AbstractAccessRight $accessRight
     * @param string $group
     */
    public function addAccessRight($id, AbstractAccessRight $accessRight, $group = 'Misc')
    {
        $accessRight->setId($id);
        $this->rights[$id] = $accessRight;
        if (!isset($this->groups[$group])) {
            $this->groups[$group] = array();
        }
        $this->groups[$group][] = $id;
    }

    /**
     * @param $id
     *
     * @return null|AccessRightInterface
     */
    public function getAccessRightById($id)
    {
        return isset($this->rights[$id]) ? $this->rights[$id] : null;
    }

    /**
     * Check if a user and not just a group have a special right.
     *
     * @param $id
     * @param User $user
     *
     * @return bool
     */
    public function hasAccessRight($id, User $user = null)
    {
/*        if ($this->security === null) {
            $this->security = $this->container->get('security.context');
        }*/

        if ($user === null) {
            $user = $this->security->getToken()->getUser();
        }

        if (!($user instanceof User)) {
            return false;
        }

        $userAccessRights = $user->getAccessRights();
        $userRoles = $user->getRoles();
        $idRights = array();
        foreach ($userAccessRights as $right) {
            $idRights[] = $this->getByName($right);
        }

        return in_array($id, $idRights) || in_array('ROLE_ADMIN', $userRoles, true);
    }

    /**
     * returns known rigths.
     *
     * @return array
     */
    public function getAccessRights()
    {
        return $this->rights;
    }

    /**
     * returns known groups.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }


    /**
     * @param array $rights
     */
    public function setRights($rights)
    {
        $this->rights = $rights;
    }

    /**
     * @return array
     */
    public function getRights()
    {
        return $this->rights;
    }

    /**
     * @param \Symfony\Component\Security\Core\Security $security
     */
    public function setSecurity($security)
    {
        $this->security = $security;
    }

    /**
     * @return \Symfony\Component\Security\Core\Security
     */
    public function getSecurity()
    {
        return $this->security;
    }

    /**
     * @param $accessRightName
     *
     * @return array|void
     */
    public function getByName($accessRightName)
    {
        $id = 100000;
        $id = array_search($accessRightName, self::RIGHT_NAMES);
        if ($id === false) {
        } else {
            if (isset($this->rights[$id])) {
                return $id;
            }
        }

        return;
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function getNameById($id)
    {
        $name = "sygefor";
        if (isset (self::RIGHT_NAMES[$id])) {
            $name = self::RIGHT_NAMES[$id];
            if (isset($this->rights[$id])) {
                return $name;
            }
        }

        return;
    }
}
