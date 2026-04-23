<?php
// src/Security/AdUserProvider.php
namespace App\Security;

use App\Entity\SecUser;
use App\Repository\SecUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\Ldap\Exception\ConnectionException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class AdUserProvider implements UserProviderInterface, PasswordUpgraderInterface
{
    public function __construct(
        private SecUserRepository $userRepository,
        private EntityManagerInterface $em,
        private Ldap $ldap,
        private string $baseDn,
        private string $searchUser,
        private string $searchPassword
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // 1. Erst lokal schauen
        $user = $this->userRepository->findOneBy(['email' => $identifier]);

        // 2. Wenn nicht da, im AD suchen & importieren
        if (!$user) {
            $user = $this->importUserFromAd($identifier);
        }

        if (!$user) {
            throw new UserNotFoundException(sprintf('User "%s" not found.', $identifier));
        }

        return $user;
    }

    private function importUserFromAd(string $identifier): ?SecUser
    {
        try {
            // Bind mit Service-User
            $this->ldap->bind($this->searchUser, $this->searchPassword);

            $identifier = ldap_escape($identifier, '', LDAP_ESCAPE_FILTER);
            // Suche nach sAMAccountName (Windows Login) oder mail
            $query = $this->ldap->query($this->baseDn, "(&(objectClass=person)(|(sAMAccountName=$identifier)(mail=$identifier)))");
            $results = $query->execute();
            $entry = $results[0] ?? null;

            if (!$entry) return null;

            $adAttributes = $entry->getAttributes();

            $newUser = new SecUser();
            // Fallback, falls 'mail' im AD leer ist: username@dummy.local
            $mail = $adAttributes['mail'][0] ?? $identifier . '@ad-import.local';

            $newUser->setEmail($mail);
            $newUser->setFirstname($adAttributes['givenName'][0] ?? '');
            $newUser->setLastname($adAttributes['sn'][0] ?? ''); // sn = Nachname
            $newUser->setRoles(['ROLE_USER']);
            $newUser->setPassword(null); // Passwort macht das AD

            $this->em->persist($newUser);
            $this->em->flush();

            return $newUser;

        } catch (\Exception $e) {
            // Loggen Sie den Fehler im echten Betrieb!
            return null;
        }
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->loadUserByIdentifier($user->getEmail());
    }

    public function supportsClass(string $class): bool
    {
        return SecUser::class === $class || is_subclass_of($class, SecUser::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
    }
}