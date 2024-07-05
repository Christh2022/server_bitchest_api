<?php

namespace App\Entity;

use App\Repository\CryptoCurrenciesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CryptoCurrenciesRepository::class)]
class CryptoCurrencies
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $symbol = null;

    /**
     * @var Collection<int, Wallets>
     */
    #[ORM\ManyToMany(targetEntity: Wallets::class, mappedBy: 'cryptoId')]
    private Collection $wallets;

    #[ORM\ManyToOne(inversedBy: 'crypto_id')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CryptoPrices $cryptoPrices = null;

    #[ORM\ManyToOne(inversedBy: 'Crypto')]
    private ?Wallets $WalletsCrypto = null;

    public function __construct()
    {
        $this->wallets = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * @return Collection<int, Wallets>
     */
    public function getWallets(): Collection
    {
        return $this->wallets;
    }

    public function addWallet(Wallets $wallet): static
    {
        if (!$this->wallets->contains($wallet)) {
            $this->wallets->add($wallet);
            $wallet->addCryptoId($this);
        }

        return $this;
    }

    public function removeWallet(Wallets $wallet): static
    {
        if ($this->wallets->removeElement($wallet)) {
            $wallet->removeCryptoId($this);
        }

        return $this;
    }

    public function getCryptoPrices(): ?CryptoPrices
    {
        return $this->cryptoPrices;
    }

    public function setCryptoPrices(?CryptoPrices $cryptoPrices): static
    {
        $this->cryptoPrices = $cryptoPrices;

        return $this;
    }

    public function getWalletsCrypto(): ?Wallets
    {
        return $this->WalletsCrypto;
    }

    public function setWalletsCrypto(?Wallets $WalletsCrypto): static
    {
        $this->WalletsCrypto = $WalletsCrypto;

        return $this;
    }
}
