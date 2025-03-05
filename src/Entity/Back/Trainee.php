<?php

namespace App\Entity\Back;


use App\Entity\Core\AbstractInscription;
use Symfony\Component\Security\Core\User\UserInterface;
use App\Form\Type\AbstractTraineeType;
use App\Entity\Core\AbstractTrainee;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Core\User;

#[ORM\Table(name: 'trainee')]
#[ORM\Entity]
#[UniqueEntity(fields: ['email', 'institution'], message: 'Cette adresse email est déjà utilisée.', ignoreNull: true, groups: ['Default', 'trainee'])]
class Trainee extends AbstractTrainee
{
    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'birth_date', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $birthdate = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'amu_statut', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $amustatut = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'bap', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $bap = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'corps', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $corps = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'category', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $category = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'campus', type: \Doctrine\DBAL\Types\Types::STRING, length: 20)]
    protected ?string $campus = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'first_name_sup', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $firstnamesup = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'last_name_sup', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $lastnamesup = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'email_sup', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $emailsup = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'first_name_corr', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $firstnamecorr = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'last_name_corr', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $lastnamecorr = null;

    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'email_corr', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $emailcorr = null;


    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\Column(name: 'fonction', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    protected ?string $fonction = null;

    /**
     * @var \Doctrine\Common\Collections\Collection<Alert>
     */
    #[Groups(['Default', 'trainee', 'api'])]
    #[ORM\OneToMany(mappedBy: 'trainee', targetEntity: Alert::class, cascade: ['remove'])]
    protected \Doctrine\Common\Collections\Collection $alerts;

    /**
     * @return mixed
     */
    static public function getFormType(): string
    {
        return AbstractTraineeType::class;
    }

    /**
     * Set shibboleth persistent id
     *
     *
     * @return Trainee
     */

    /**
     * Set birth date
     *
     *
     * @return Trainee
     */
    public function setBirthdate(mixed $birthDate)
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
     *
     * @return Trainee
     */
    public function setAmustatut(mixed $amuStatut)
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
     *
     * @return Trainee
     */
    public function setBap(mixed $bap)
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
     *
     * @return Trainee
     */
    public function setCorps(mixed $corps)
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
     *
     * @return Trainee
     */
    public function setCategory(mixed $category)
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
     *
     * @return Trainee
     */
    public function setCampus(mixed $campus)
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
     */
    public function removeInscription(AbstractInscription $inscription): void
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
    public function setAlerts($alerts): void
    {
        $this->alerts = $alerts;
    }

    /**
     * @param Alert $alerts
     *
     */
    public function addAlert($alert): bool
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
     */
    public function removeAlert($alert): bool
    {
        if ($this->alerts->contains($alert)) {
            $this->alerts->removeElement($alert);

            return true;
        }

        return false;
    }
    public function __construct()
    {
        parent::__construct();
        $this->alerts = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function __serialize(): array
    {
        return array();// TODO: Implement __serialize() method.
    }

    public function __unserialize(array $data): void
    {
        $this->$data = $data;
        // TODO: Implement __unserialize() method.
    }
}
