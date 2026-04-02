<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(fields: ['email'], message: 'Este e-mail já está em uso.')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'app_user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Informe um e-mail.')]
    #[Assert\Email(message: 'Informe um e-mail válido.')]
    #[ORM\Column(length: 180, unique: true)]
    private string $email = '';

    /**
     * @var list<string>
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $jobDescription = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $defaultDailyRate = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $defaultHourlyRate = null;

    #[ORM\Column(type: 'decimal', precision: 6, scale: 2, nullable: true)]
    private ?string $defaultHourlyHoursPerBusinessDay = null;

    #[ORM\Column(length: 3, nullable: true)]
    private ?string $defaultDailyRateCurrency = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = mb_strtolower(trim($email));

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    public function getJobDescription(): ?string
    {
        return $this->jobDescription;
    }

    public function setJobDescription(?string $jobDescription): self
    {
        $this->jobDescription = $jobDescription !== null ? trim($jobDescription) : null;

        return $this;
    }

    public function getDefaultDailyRate(): ?string
    {
        return $this->defaultDailyRate;
    }

    public function setDefaultDailyRate(?string $defaultDailyRate): self
    {
        $this->defaultDailyRate = $defaultDailyRate;

        return $this;
    }

    public function getDefaultDailyRateCurrency(): ?string
    {
        return $this->defaultDailyRateCurrency;
    }

    public function setDefaultDailyRateCurrency(?string $defaultDailyRateCurrency): self
    {
        $this->defaultDailyRateCurrency = $defaultDailyRateCurrency !== null
            ? mb_strtoupper(trim($defaultDailyRateCurrency))
            : null;

        return $this;
    }

    public function getDefaultHourlyRate(): ?string
    {
        return $this->defaultHourlyRate;
    }

    public function setDefaultHourlyRate(?string $defaultHourlyRate): self
    {
        $this->defaultHourlyRate = $defaultHourlyRate;

        return $this;
    }

    public function getDefaultHourlyHoursPerBusinessDay(): ?string
    {
        return $this->defaultHourlyHoursPerBusinessDay;
    }

    public function setDefaultHourlyHoursPerBusinessDay(?string $defaultHourlyHoursPerBusinessDay): self
    {
        $this->defaultHourlyHoursPerBusinessDay = $defaultHourlyHoursPerBusinessDay;

        return $this;
    }
}
