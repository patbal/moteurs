<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MoteurRepository")
 */
class Moteur
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=12)
     */
    private $numeroMoteur;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    private $typeMoteur;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $urlPV;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    private $urlQRCode;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $enService;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $controlOK;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\CarnetMoteur", mappedBy="moteur")
     */
    private $carnet;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\TypeMateriel")
     * @ORM\JoinColumn(nullable=false)
     */
    private $type;

    public function __construct()
    {
        $this->carnet = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumeroMoteur(): ?string
    {
        return $this->numeroMoteur;
    }

    public function setNumeroMoteur(string $numeroMoteur): self
    {
        $this->numeroMoteur = $numeroMoteur;

        return $this;
    }

    public function getTypeMoteur(): ?string
    {
        return $this->typeMoteur;
    }

    public function setTypeMoteur(?string $typeMoteur): self
    {
        $this->typeMoteur = $typeMoteur;

        return $this;
    }

    public function getUrlPV(): ?string
    {
        return $this->urlPV;
    }

    public function setUrlPV(?string $urlPV): self
    {
        $this->urlPV = $urlPV;

        return $this;
    }

    public function getUrlQRCode(): ?string
    {
        return $this->urlQRCode;
    }

    public function setUrlQRCode(?string $urlQRCode): self
    {
        $this->urlQRCode = $urlQRCode;

        return $this;
    }

    public function getEnService(): ?bool
    {
        return $this->enService;
    }

    public function setEnService(?bool $enService): self
    {
        $this->enService = $enService;

        return $this;
    }

    public function getControlOK(): ?bool
    {
        return $this->controlOK;
    }

    public function setControlOK(?bool $controlOK): self
    {
        $this->controlOK = $controlOK;

        return $this;
    }

    /**
     * @return Collection|CarnetMoteur[]
     */
    public function getCarnet(): Collection
    {
        return $this->carnet;
    }

    public function addCarnet(CarnetMoteur $carnet): self
    {
        if (!$this->carnet->contains($carnet)) {
            $this->carnet[] = $carnet;
            $carnet->setMoteur($this);
        }

        return $this;
    }

    public function removeCarnet(CarnetMoteur $carnet): self
    {
        if ($this->carnet->contains($carnet)) {
            $this->carnet->removeElement($carnet);
            // set the owning side to null (unless already changed)
            if ($carnet->getMoteur() === $this) {
                $carnet->setMoteur(null);
            }
        }

        return $this;
    }

    public function getType(): ?TypeMateriel
    {
        return $this->type;
    }

    public function setType(?TypeMateriel $type): self
    {
        $this->type = $type;

        return $this;
    }
}
