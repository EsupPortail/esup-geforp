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
        $accessRight = new AllTrainingUpdateAccessRight();
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
        switch ($accessRightName) {
            case "sygefor_core.rights.user.own":
                $id = 0;
                break;
            case "sygefor_core.rights.user.all":
                $id = 1;
                break;
            case "sygefor_core.rights.vocabulary.own":
                $id = 2;
                break;
            case "sygefor_core.rights.vocabulary.national":
                $id = 3;
                break;
            case "sygefor_core.rights.vocabulary.all":
                $id = 4;
                break;
            case 'sygefor_training.rights.training.own.view':
                $id = 5;
                break;
            case 'sygefor_training.rights.training.own.create':
                $id = 6;
                break;
            case 'sygefor_training.rights.training.own.update':
                $id = 7;
                break;
            case 'sygefor_training.rights.training.own.delete':
                $id = 8;
                break;
            case 'sygefor_training.rights.training.all.view':
                $id = 9;
                break;
            case 'sygefor_training.rights.training.all.create':
                $id = 10;
                break;
            case 'sygefor_training.rights.training.all.update':
                $id = 11;
                break;
            case 'sygefor_training.rights.training.all.delete':
                $id = 12;
                break;
            case 'sygefor_trainee.rights.trainee.own.view':
                $id = 13;
                break;
            case 'sygefor_trainee.rights.trainee.own.create':
                $id = 14;
                break;
            case 'sygefor_trainee.rights.trainee.own.update':
                $id = 15;
                break;
            case 'sygefor_trainee.rights.trainee.own.delete':
                $id = 16;
                break;
            case 'sygefor_trainee.rights.trainee.all.view':
                $id = 17;
                break;
            case 'sygefor_trainee.rights.trainee.all.create':
                $id = 18;
                break;
            case 'sygefor_trainee.rights.trainee.all.update':
                $id = 19;
                break;
            case 'sygefor_trainee.rights.trainee.all.delete':
                $id = 20;
                break;
            case 'sygefor_inscription.rights.inscription.own.view':
                $id = 21;
                break;
            case 'sygefor_inscription.rights.inscription.own.create':
                $id = 22;
                break;
            case 'sygefor_inscription.rights.inscription.own.update':
                $id = 23;
                break;
            case 'sygefor_inscription.rights.inscription.own.delete':
                $id = 24;
                break;
            case 'sygefor_inscription.rights.inscription.all.view':
                $id = 25;
                break;
            case 'sygefor_inscription.rights.inscription.all.create':
                $id = 26;
                break;
            case 'sygefor_inscription.rights.inscription.all.update':
                $id = 27;
                break;
            case 'sygefor_inscription.rights.inscription.all.delete':
                $id = 28;
                break;
            case 'sygefor_institution.rights.institution.own.view':
                $id = 29;
                break;
            case 'sygefor_institution.rights.institution.own.create':
                $id = 30;
                break;
            case 'sygefor_institution.rights.institution.own.update':
                $id = 31;
                break;
            case 'sygefor_institution.rights.institution.own.delete':
                $id = 32;
                break;
            case 'sygefor_institution.rights.institution.all.view':
                $id = 33;
                break;
            case 'sygefor_institution.rights.institution.all.create':
                $id = 34;
                break;
            case 'sygefor_institution.rights.institution.all.update':
                $id = 35;
                break;
            case 'sygefor_institution.rights.institution.all.delete':
                $id = 36;
                break;
            case 'sygefor_trainer.rights.trainer.own.view':
                $id = 37;
                break;
            case 'sygefor_trainer.rights.trainer.own.create':
                $id = 38;
                break;
            case 'sygefor_trainer.rights.trainer.own.update':
                $id = 39;
                break;
            case 'sygefor_trainer.rights.trainer.own.delete':
                $id = 40;
                break;
            case 'sygefor_trainer.rights.trainer.all.view':
                $id = 41;
                break;
            case 'sygefor_trainer.rights.trainer.all.create':
                $id = 42;
                break;
            case 'sygefor_trainer.rights.trainer.all.update':
                $id = 43;
                break;
            case 'sygefor_trainer.rights.trainer.all.delete':
                $id = 44;
                break;
        }
        if (isset($this->rights[$id])) {
            return $id;
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
        switch ($id) {
            case 0:
                $name ="sygefor_core.rights.user.own";
                break;
            case 1:
                $name ="sygefor_core.rights.user.all";
                break;
            case 2:
                $name ="sygefor_core.rights.vocabulary.own";
                break;
            case 3:
                $name ="sygefor_core.rights.vocabulary.national";
                break;
            case 4:
                $name ="sygefor_core.rights.vocabulary.all";
                break;
            case 5:
                $name ="sygefor_training.rights.training.own.view";
                break;
            case 6:
                $name ="sygefor_training.rights.training.own.create";
                break;
            case 7:
                $name ="sygefor_training.rights.training.own.update";
                break;
            case 8:
                $name ="sygefor_training.rights.training.own.delete";
                break;
            case 9:
                $name ="sygefor_training.rights.training.all.view";
                break;
            case 10:
                $name ="sygefor_training.rights.training.all.create";
                break;
            case 11:
                $name ="sygefor_training.rights.training.all.update";
                break;
            case 12:
                $name ="sygefor_training.rights.training.all.delete";
                break;
            case 13:
                $name ="sygefor_trainee.rights.trainee.own.view";
                break;
            case 14:
                $name ="sygefor_trainee.rights.trainee.own.create";
                break;
            case 15:
                $name ="sygefor_trainee.rights.trainee.own.update";
                break;
            case 16:
                $name ="sygefor_trainee.rights.trainee.own.delete";
                break;
            case 17:
                $name ="sygefor_trainee.rights.trainee.all.view";
                break;
            case 18:
                $name ="sygefor_trainee.rights.trainee.all.create";
                break;
            case 19:
                $name ="sygefor_trainee.rights.trainee.all.update";
                break;
            case 20:
                $name ="sygefor_trainee.rights.trainee.all.delete";
                break;
            case 21:
                $name ="sygefor_inscription.rights.inscription.own.view";
                break;
            case 22:
                $name ="sygefor_inscription.rights.inscription.own.create";
                break;
            case 23:
                $name ="sygefor_inscription.rights.inscription.own.update";
                break;
            case 24:
                $name ="sygefor_inscription.rights.inscription.own.delete";
                break;
            case 25:
                $name ="sygefor_inscription.rights.inscription.all.view";
                break;
            case 26:
                $name ="sygefor_inscription.rights.inscription.all.create";
                break;
            case 27:
                $name ="sygefor_inscription.rights.inscription.all.update";
                break;
            case 28:
                $name ="sygefor_inscription.rights.inscription.all.delete";
                break;
            case 29:
                $name ="sygefor_institution.rights.institution.own.view";
                break;
            case 30:
                $name ="sygefor_institution.rights.institution.own.create";
                break;
            case 31:
                $name ="sygefor_institution.rights.institution.own.update";
                break;
            case 32:
                $name ="sygefor_institution.rights.institution.own.delete";
                break;
            case 33:
                $name ="sygefor_institution.rights.institution.all.view";
                break;
            case 34:
                $name ="sygefor_institution.rights.institution.all.create";
                break;
            case 35:
                $name ="sygefor_institution.rights.institution.all.update";
                break;
            case 36:
                $name ="sygefor_institution.rights.institution.all.delete";
                break;
            case 37:
                $name ="sygefor_trainer.rights.trainer.own.view";
                break;
            case 38:
                $name ="sygefor_trainer.rights.trainer.own.create";
                break;
            case 39:
                $name ="sygefor_trainer.rights.trainer.own.update";
                break;
            case 40:
                $name ="sygefor_trainer.rights.trainer.own.delete";
                break;
            case 41:
                $name ="sygefor_trainer.rights.trainer.all.view";
                break;
            case 42:
                $name ="sygefor_trainer.rights.trainer.all.create";
                break;
            case 43:
                $name ="sygefor_trainer.rights.trainer.all.update";
                break;
            case 44:
                $name ="sygefor_trainer.rights.trainer.all.delete";
                break;
        }
        if (isset($this->rights[$id])) {
            return $name;
        }

        return;
    }
}
