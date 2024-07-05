<?php

namespace App\Entity;

use App\Repository\WalletsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[ORM\Entity(repositoryClass: WalletsRepository::class)]
class Wallets extends AbstractController
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Clients::class)]
    #[ORM\JoinColumn(name: "client_id", referencedColumnName: "id", nullable: true)]
    private ?Clients $client;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank] // Ensure that the quantity is not blank
    private ?string $quantity;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank] // Ensure that the average purchase price is not blank
    private ?string $averagePurchasePrice;

    /**
     * @var Collection<int, Transactions>
     */
    #[ORM\OneToMany(targetEntity: Transactions::class, mappedBy: 'wallet_id')]
    private Collection $transactions;

    /**
     * @var Collection<int, CryptoCurrencies>
     */
    #[ORM\ManyToMany(targetEntity: CryptoCurrencies::class, inversedBy: 'wallets')]
    private Collection $cryptoId;

    /**
     * @var Collection<int, CryptoCurrencies>
     */
    #[ORM\OneToMany(targetEntity: CryptoCurrencies::class, mappedBy: 'WalletsCrypto')]
    private Collection $Crypto;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $this->cryptoId = new ArrayCollection();
        $this->Crypto = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Clients
    {
        return $this->client;
    }

    public function setClient(?Clients $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(?string $quantity): self
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getAveragePurchasePrice(): ?string
    {
        return $this->averagePurchasePrice;
    }

    public function setAveragePurchasePrice(?string $averagePurchasePrice): self
    {
        $this->averagePurchasePrice = $averagePurchasePrice;
        return $this;
    }

    /**
     * @return Collection<int, Transactions>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transactions $transaction): static
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions->add($transaction);
            $transaction->setWalletId($this);
        }

        return $this;
    }

    public function removeTransaction(Transactions $transaction): static
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getWalletId() === $this) {
                $transaction->setWalletId(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CryptoCurrencies>
     */
    public function getCryptoId(): Collection
    {
        return $this->cryptoId;
    }

    public function addCryptoId(CryptoCurrencies $cryptoId): static
    {
        $this->cryptoId->add($cryptoId);
        // if (!$this->cryptoId->contains($cryptoId)) {
        // }

        return $this;
    }

    public function removeCryptoId(CryptoCurrencies $cryptoId): static
    {
        $this->cryptoId->removeElement($cryptoId);

        return $this;
    }

    /**
     * @return Collection<int, CryptoCurrencies>
     */
    public function getCrypto(): Collection
    {
        return $this->Crypto;
    }

    public function addCrypto(CryptoCurrencies $crypto): static
    {
        if (!$this->Crypto->contains($crypto)) {
            $this->Crypto->add($crypto);
            $crypto->setWalletsCrypto($this);
        }

        return $this;
    }

    public function removeCrypto(CryptoCurrencies $crypto): static
    {
        if ($this->Crypto->removeElement($crypto)) {
            // set the owning side to null (unless already changed)
            if ($crypto->getWalletsCrypto() === $this) {
                $crypto->setWalletsCrypto(null);
            }
        }

        return $this;
    }
}
