<?php

namespace App\Entity;

use App\Repository\ClientsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClientsRepository::class)]
class Clients
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'client_id')]
    private Collection $client_id;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    private ?string $lastname = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 0)]
    private ?string $balance = null;

    #[ORM\OneToOne(mappedBy: 'client', cascade: ['persist', 'remove'])]
    private ?Wallets $wallets = null;

    public function __construct()
    {
        $this->client_id = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, User>
     */
    public function getClientId(): Collection
    {
        return $this->client_id;
    }

    public function addClientId(User $clientId): static
    {
        if (!$this->client_id->contains($clientId)) {
            $this->client_id->add($clientId);
            $clientId->setClientId($this);
        }

        return $this;
    }

    public function removeClientId(User $clientId): static
    {
        if ($this->client_id->removeElement($clientId)) {
            // set the owning side to null (unless already changed)
            if ($clientId->getClientId() === $this) {
                $clientId->setClientId(null);
            }
        }

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getBalance(): ?string
    {
        return $this->balance;
    }

    public function setBalance(string $balance): static
    {
        $this->balance = $balance;

        return $this;
    }

    public function getWallets(): ?Wallets
    {
        return $this->wallets;
    }

    public function setWallets(?Wallets $wallets): static
    {
        // unset the owning side of the relation if necessary
        if ($wallets === null && $this->wallets !== null) {
            $this->wallets->setClient(null);
        }

        // set the owning side of the relation if necessary
        if ($wallets !== null && $wallets->getClient() !== $this) {
            $wallets->setClient($this);
        }

        $this->wallets = $wallets;

        return $this;
    }
}
