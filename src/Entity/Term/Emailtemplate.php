<?php

namespace App\Entity\Term;

use App\Form\Type\EmailTemplateVocabularyType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Emailtemplate.
 *
 */
#[ORM\Table(name: 'trainee_email_template')]
#[ORM\Entity]
class Emailtemplate extends AbstractTerm implements VocabularyInterface
{
    #[ORM\Column(name: 'subject', type: \Doctrine\DBAL\Types\Types::STRING, length: 255)]
    private ?string $subject = null;

    #[ORM\Column(name: 'cc', type: 'simple_array', nullable: true)]
    private array $cc = [];

    #[ORM\Column(name: 'body', type: \Doctrine\DBAL\Types\Types::TEXT)]
    private ?string $body = null;

    /**
     *
     * @var Inscriptionstatus
     */
    #[ORM\ManyToOne(targetEntity: 'Inscriptionstatus')]
    #[ORM\JoinColumn(name: 'inscription_status_id')]
    protected Inscriptionstatus $inscriptionstatus;

    #[ORM\ManyToOne(targetEntity: 'Presencestatus')]
    #[ORM\JoinColumn(name: 'presence_status_id')]
    protected Presencestatus $presencestatus;

    /**
     * @var \Doctrine\Common\Collections\Collection<\App\Entity\Term\PublipostTemplate>
     */
    #[ORM\JoinTable(name: 'email_templates__publipost_templates')]
    #[ORM\JoinColumn(name: 'email_template_id')]
    #[ORM\InverseJoinColumn(name: 'publipost_template_id', referencedColumnName: 'id')]
    #[ORM\ManyToMany(targetEntity: 'PublipostTemplate')]
    protected \Doctrine\Common\Collections\Collection $attachmentTemplates;

    /**
     * @param ArrayCollection $attachmentTemplates
     */
    public function setAttachmentTemplates(ArrayCollection $attachmentTemplates): void
    {
        $this->attachmentTemplates = $attachmentTemplates;
    }

    /**
     * @return ArrayCollection
     */
    public function getAttachmentTemplates(): ArrayCollection|\Doctrine\Common\Collections\Collection
    {
        return $this->attachmentTemplates;
    }

    public function setBody(mixed $body): void
    {
        $this->body = $body;
    }

    /**
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->body;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string|null
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * @return array
     */
    public function getCc(): array
    {
        return $this->cc;
    }

    /**
     * @param array $cc
     */
    public function setCc(array $cc): void
    {
        $this->cc = $cc;
    }

    /**
     * @param Inscriptionstatus $inscriptionStatus
     */
    public function setInscriptionstatus(Inscriptionstatus $inscriptionStatus): void
    {
        $this->inscriptionstatus = $inscriptionStatus;
    }

    /**
     * @return Inscriptionstatus
     */
    public function getInscriptionstatus(): Inscriptionstatus
    {
        return $this->inscriptionstatus;
    }

    /**
     * @param Presencestatus $presenceStatus
     */
    public function setPresencestatus(Presencestatus $presenceStatus): void
    {
        $this->presencestatus = $presenceStatus;
    }

    /**
     * @return Presencestatus
     */
    public function getPresencestatus(): Presencestatus
    {
        return $this->presencestatus;
    }

    /**
     * @return mixed
     */
    public function getVocabularyName(): string
    {
        return 'Modèles d\'emails';
    }

    /**
     * returns the form type name for template edition.
     *
     */
    public static function getFormType(): string
    {
        return EmailTemplateVocabularyType::class;
    }

    public static function getVocabularyStatus(): int
    {
        return VocabularyInterface::VOCABULARY_LOCAL;
    }
    public function __construct()
    {
        $this->attachmentTemplates = new \Doctrine\Common\Collections\ArrayCollection();
    }
}