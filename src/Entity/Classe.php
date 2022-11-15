<?php

namespace App\Entity;

use App\Repository\ClasseRepository;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;


/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "app_detail_classe",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getClasse")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "app_delete_classe",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getClasse", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "app_update_classe",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getClasse", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/

#[ORM\Entity(repositoryClass: ClasseRepository::class)]
class Classe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getClasse", "getProf", "getEleve"])]
    #[Since("1.0")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getClasse", "getProf", "getEleve"])]
    #[Assert\NotBlank(message: "Le nom de la classe est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le nom de la classe doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le nom de la classe ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'Classe')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["getClasse"])]
    #[Assert\NotBlank(message: "Le nom du professeur est obligatoire")]
    #[Since("1.0")]
    private ?Professeur $professeur = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getProfesseur(): ?Professeur
    {
        return $this->professeur;
    }

    public function setProfesseur(?Professeur $professeur): self
    {
        $this->professeur = $professeur;

        return $this;
    }
}
