<?php

namespace App\Entity;

use App\Repository\CryptoPricesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CryptoPricesRepository::class)]
class CryptoPrices
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, CryptoCurrencies>
     */
    #[ORM\OneToMany(targetEntity: CryptoCurrencies::class, mappedBy: 'cryptoPrices')]
    private Collection $crypto_id;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $price = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    public function __construct()
    {
        $this->crypto_id = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, CryptoCurrencies>
     */
    public function getCryptoId(): Collection
    {
        return $this->crypto_id;
    }

    public function addCryptoId(CryptoCurrencies $cryptoId): static
    {
        if (!$this->crypto_id->contains($cryptoId)) {
            $this->crypto_id->add($cryptoId);
            $cryptoId->setCryptoPrices($this);
        }

        return $this;
    }

    public function removeCryptoId(CryptoCurrencies $cryptoId): static
    {
        if ($this->crypto_id->removeElement($cryptoId)) {
            // set the owning side to null (unless already changed)
            if ($cryptoId->getCryptoPrices() === $this) {
                $cryptoId->setCryptoPrices(null);
            }
        }

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
