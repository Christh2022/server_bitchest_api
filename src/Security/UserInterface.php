<?php
// src/Security/UserInterface.php

namespace App\Security;

use App\Entity\Clients;
use Symfony\Component\Security\Core\User\UserInterface as BaseUserInterface;

interface UserInterface extends BaseUserInterface
{
    /**
     * Returns the client ID associated with the user.
     *
     * @return Clients|null The client ID associated with the user
     */
    public function getClientId(): ?Clients;
}
