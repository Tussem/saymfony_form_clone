<?php

namespace App\Entity;

use App\Repository\ResponseRepository;
use Doctrine\ORM\Mapping as ORM;
use DateTimeImmutable;

#[ORM\Entity(repositoryClass: ResponseRepository::class)]
class Response
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Form::class, inversedBy: 'responses')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Form $form = null;

    #[ORM\Column(type: "json")]
    private array $answers = [];

    #[ORM\Column(type: "datetime_immutable")]
    private ?DateTimeImmutable $submittedAt = null;


    public function __construct()
    {
        $this->submittedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): self
    {
        $this->form = $form;

        return $this;
    }

    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function setAnswers(array $answers): self
    {
        $this->answers = $answers;

        return $this;
    }

    public function getSubmittedAt(): ?DateTimeImmutable 
    { 
        return $this->submittedAt; 
    
    }
}
