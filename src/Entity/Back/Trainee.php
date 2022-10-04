<?php

namespace App\Entity\Back;


use App\Entity\Core\AbstractInscription;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Form\Type\AbstractTraineeType;
use App\Entity\Core\AbstractTrainee;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Core\User;

/**
 *
 * @ORM\Table(name="trainee")
 * @ORM\Entity
 * @UniqueEntity(fields={"email", "organization"}, message="Cette adresse email est déjà utilisée.", ignoreNull=true, groups={"Default", "trainee"})
 */
class Trainee extends AbstractTrainee implements UserInterface
{
    /**
     * @ORM\Column(name="birth_date", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $birthdate;

    /**
     * @ORM\Column(name="amu_statut", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $amustatut;

    /**
     * @ORM\Column(name="bap", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $bap;

    /**
     * @ORM\Column(name="corps", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $corps;

    /**
     * @ORM\Column(name="category", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $category;

    /**
     * @ORM\Column(name="campus", type="string", length=20)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $campus;

    /**
     * @ORM\Column(name="first_name_sup", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $firstnamesup;

    /**
     * @ORM\Column(name="last_name_sup", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $lastnamesup;

    /**
     * @ORM\Column(name="email_sup", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $emailsup;

    /**
     * @ORM\Column(name="first_name_corr", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $firstnamecorr;

    /**
     * @ORM\Column(name="last_name_corr", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $lastnamecorr;

    /**
     * @ORM\Column(name="email_corr", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $emailcorr;

    /**
     * @ORM\Column(name="fonction", type="string", length=255)
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $fonction;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Back\Alert", mappedBy="trainee", cascade={"remove"})
     * @Serializer\Groups({"Default", "trainee", "api"})
     */
    protected $alerts;

    /**
     * @return mixed
     */
    static public function getFormType()
    {
        return AbstractTraineeType::class;
    }

    /**
     * Set birth date
     *
     * @param mixed $birthDate
     *
     * @return Trainee
     */
    public function setBirthdate($birthDate)
    {
        $this->birthdate = $birthDate;

        return $this;
    }

    /**
     * Get birth date
     *
     */
    public function getBirthdate()
    {
        return $this->birthdate;
    }

    /**
     * Set amuStatut
     *
     * @param mixed $amuStatut
     *
     * @return Trainee
     */
    public function setAmustatut($amuStatut)
    {
        $this->amustatut = $amuStatut;

        return $this;
    }

    /**
     * Get amuStatut
     *
     */
    public function getAmustatut()
    {
        return $this->amustatut;
    }

    /**
     * Set bap
     *
     * @param mixed $bap
     *
     * @return Trainee
     */
    public function setBap($bap)
    {
        $this->bap = $bap;

        return $this;
    }

    /**
     * Get bap
     *
     */
    public function getBap()
    {
        return $this->bap;
    }


    /**
     * Set corps
     *
     * @param mixed $corps
     *
     * @return Trainee
     */
    public function setCorps($corps)
    {
        $this->corps = $corps;

        return $this;
    }

    /**
     * Get corps
     *
     */
    public function getCorps()
    {
        return $this->corps;
    }

    /**
     * Set category
     *
     * @param mixed $category
     *
     * @return Trainee
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set campus
     *
     * @param mixed $campus
     *
     * @return Trainee
     */
    public function setCampus($campus)
    {
        $this->campus = $campus;

        return $this;
    }

    /**
     * Get campus
     *
     */
    public function getCampus()
    {
        return $this->campus;
    }

    /**
     * Set firstnameSup
     *
     * @param string $firstNameSup
     *
     * @return Trainee
     */
    public function setFirstnamesup($firstNameSup)
    {
        $this->firstnamesup = $firstNameSup;

        return $this;
    }

    /**
     * Get firstnameSup
     *
     * @return string
     */
    public function getFirstnamesup()
    {
        return $this->firstnamesup;
    }

    /**
     * Set lastnameSup
     *
     * @param string $lastnamesup
     *
     * @return Trainee
     */
    public function setLastnamesup($lastNameSup)
    {
        $this->lastnamesup = $lastNameSup;

        return $this;
    }

    /**
     * Get lastnameSup
     *
     * @return string
     */
    public function getLastnamesup()
    {
        return $this->lastnamesup;
    }

    /**
     * Set emailSup
     *
     * @param string $emailSup
     *
     * @return Trainee
     */
    public function setEmailsup($emailSup)
    {
        $this->emailsup = $emailSup;

        return $this;
    }

    /**
     * Get emailSup
     *
     * @return string
     */
    public function getEmailsup()
    {
        return $this->emailsup;
    }

    /**
     * Set firstnameCorr
     *
     * @param string $firstnamecorr
     *
     * @return Trainee
     */
    public function setFirstnamecorr($firstNameCorr)
    {
        $this->firstnamecorr = $firstNameCorr;

        return $this;
    }

    /**
     * Get firstnameCorr
     *
     * @return string
     */
    public function getFirstnamecorr()
    {
        return $this->firstnamecorr;
    }

    /**
     * Set lastnameCorr
     *
     * @param string $lastNameCorr
     *
     * @return Trainee
     */
    public function setLastnamecorr($lastNameCorr)
    {
        $this->lastnamecorr = $lastNameCorr;

        return $this;
    }

    /**
     * Get lastnameCorr
     *
     * @return string
     */
    public function getLastnamecorr()
    {
        return $this->lastnamecorr;
    }

    /**
     * Set emailCorr
     *
     * @param string $emailCorr
     *
     * @return Trainee
     */
    public function setEmailcorr($emailCorr)
    {
        $this->emailcorr = $emailCorr;

        return $this;
    }

    /**
     * Get emailCorr
     *
     * @return string
     */
    public function getEmailcorr()
    {
        return $this->emailcorr;
    }

    /**
     * Set fonction
     *
     * @param string $fonction
     *
     * @return Trainee
     */
    public function setFonction($fonction)
    {
        $this->fonction = $fonction;

        return $this;
    }

    /**
     * Get fonction
     *
     * @return string
     */
    public function getFonction()
    {
        return $this->fonction;
    }

    /**
     * Add inscription
     *
     * @param \App\Entity\Core\AbstractInscription $inscription
     *
     * @return Trainee
     */
    public function addInscription(AbstractInscription $inscription)
    {
        $this->inscriptions[] = $inscription;

        return $this;
    }

    /**
     * Remove inscription
     *
     * @param \App\Entity\Core\AbstractInscription $inscription
     */
    public function removeInscription(AbstractInscription $inscription)
    {
        $this->inscriptions->removeElement($inscription);
    }

    /**
     * @return ArrayCollection
     */
    public function getAlerts()
    {
        return $this->alerts;
    }

    /**
     * @param ArrayCollection $alerts
     */
    public function setAlerts($alerts)
    {
        $this->alerts = $alerts;
    }

    /**
     * @param Alert $alerts
     *
     * @return bool
     */
    public function addAlert($alert)
    {
        if (!$this->alerts->contains($alert)) {
            $this->alerts->add($alert);

            return true;
        }

        return false;
    }

    /**
     * @param Alert $alert
     *
     * @return bool
     */
    public function removeAlert($alert)
    {
        if ($this->alerts->contains($alert)) {
            $this->alerts->removeElement($alert);

            return true;
        }

        return false;
    }
}
