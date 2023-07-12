<?php

namespace App\Entity;

use App\Repository\SecteurRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


/**
 * @ORM\Entity(repositoryClass=SecteurRepository::class)
 */
class Secteur
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"projectfull:read", "secteur:read", "projectwp:read"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=120)
     * @Groups({"projectfull:read", "secteur:read", "projectwp:read"})
     */
    private $libelle;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Groups({"projectfull:read", "secteur:read", "projectwp:read"})
     */
    private $idWp;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(string $libelle): self
    {
        $this->libelle = $libelle;

        return $this;
    }

    public function getIdWp(): ?int
    {
        return $this->idWp;
    }

    public function setIdWp(?int $idWp): self
    {
        $this->idWp = $idWp;

        return $this;
    }
}
