<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ProfesseurRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Hateoas\Configuration\Annotation as Hateoas;
use JMS\Serializer\Annotation\Since;
use JMS\Serializer\Annotation\Type;


/**
* @Hateoas\Relation(
* "self",
* href = @Hateoas\Route(
* "app_detail_professeur",
* parameters = { "id" = "expr(object.getId())" }
* ),
* exclusion = @Hateoas\Exclusion(groups="getProf")
* )
*
* @Hateoas\Relation(
* "delete",
* href = @Hateoas\Route(
* "app_delete_professeur",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getProf", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
* @Hateoas\Relation(
* "update",
* href = @Hateoas\Route(
* "app_update_professeur",
* parameters = { "id" = "expr(object.getId())" },
* ),
* exclusion = @Hateoas\Exclusion(groups="getProf", excludeIf = "expr(not is_granted('ROLE_ADMIN'))"),
* )
*
*/


#[ORM\Entity(repositoryClass: ProfesseurRepository::class)]

class Professeur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getProf", "getEleve", "getClasse"])]
    #[Since("1.0")]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getProf", "getEleve", "getClasse"])]
    #[Assert\NotBlank(message: "Le nom du professeur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le nom du professeur doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le prénom du professeur ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getProf", "getEleve", "getClasse"])]
    #[Assert\NotBlank(message: "Le prénom du professeur est obligatoire")]
    #[Assert\Length(
        min: 1, 
        max: 255, 
        minMessage: "Le prénom du professeur doit faire au moins {{ limit }} caractères", 
        maxMessage: "Le prénom du professeur ne peut pas faire plus de {{ limit }} caractères"
    )]
    #[Since("1.0")]
    private ?string $prenom = null;

    #[ORM\OneToMany(mappedBy: 'professeur', targetEntity: Classe::class, orphanRemoval: true)]
    #[Groups(["getProf", "getEleve" ])]
    #[Assert\NotBlank(message: "La classe est obligatoire")]
    #[Since("1.0")]
    private Collection $classe;

    #[ORM\OneToMany(mappedBy: 'professeur', targetEntity: Eleve::class)]
    #[Groups(["getProf"])]
    #[Assert\NotBlank(message: "La classe est obligatoire")]
    #[Since("1.0")]
    private Collection $eleves;

    public function __construct()
    {
        $this->classe = new ArrayCollection();
        $this->eleves = new ArrayCollection();
    }

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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * @return Collection<int, classe>
     */
    public function getClasse(): Collection
    {
        return $this->classe;
    }

    public function addClasse(Classe $classe): self
    {
        if (!$this->classe->contains($classe)) {
            $this->classe->add($classe);
            $classe->setProfesseur($this);
        }

        return $this;
    }

    public function removeClasse(Classe $classe): self
    {
        if ($this->classe->removeElement($classe)) {
            // set the owning side to null (unless already changed)
            if ($classe->getProfesseur() === $this) {
                $classe->setProfesseur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Eleve>
     */
    public function getEleves(): Collection
    {
        return $this->eleves;
    }

    public function addEleve(Eleve $eleve): self
    {
        if (!$this->eleves->contains($eleve)) {
            $this->eleves->add($eleve);
            $eleve->setProfesseur($this);
        }

        return $this;
    }

    public function removeEleve(Eleve $eleve): self
    {
        if ($this->eleves->removeElement($eleve)) {
            // set the owning side to null (unless already changed)
            if ($eleve->getProfesseur() === $this) {
                $eleve->setProfesseur(null);
            }
        }

        return $this;
    }
}
