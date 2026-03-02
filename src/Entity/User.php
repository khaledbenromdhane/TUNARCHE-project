<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "user")]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_user", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "Nom", type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: "/^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/",
        message: "Le nom ne doit contenir que des lettres."
    )]
    private ?string $nom = null;

    #[ORM\Column(name: "Prenom", type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le prénom est obligatoire.")]
    #[Assert\Length(min: 2, max: 100)]
    #[Assert\Regex(
        pattern: "/^[A-Za-zÀ-ÖØ-öø-ÿ\s'-]+$/",
        message: "Le prénom ne doit contenir que des lettres."
    )]
    private ?string $prenom = null;

    #[ORM\Column(name: "Password", type: "string", length: 255)]
    private ?string $password = null;

    #[ORM\Column(name: "Email", type: "string", length: 180, unique: true)]
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'adresse email n'est pas valide.")]
    #[Assert\Length(max: 180)]
    private ?string $email = null;

    #[ORM\Column(name: "Telephone", type: "string", length: 20)]
    #[Assert\NotBlank(message: "Le numéro de téléphone est obligatoire.")]
    #[Assert\Regex(
        pattern: "/^[0-9+\s-]{6,20}$/",
        message: "Le numéro de téléphone est invalide."
    )]
    private ?string $telephone = null;

    #[ORM\Column(name: "Role", type: "json")]
    private array $role = ['ROLE_USER'];

    // ═══════════════════════════════════════════════════════════
    // Avatar (photo de profil)
    // ═══════════════════════════════════════════════════════════
    #[ORM\Column(name: "avatar_filename", type: "string", length: 255, nullable: true)]
    private ?string $avatarFilename = null;

    // ═══════════════════════════════════════════════════════════
    // Réinitialisation de mot de passe
    // ═══════════════════════════════════════════════════════════
    #[ORM\Column(name: "reset_token", type: "string", length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(name: "reset_token_expires_at", type: "datetime", nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    // ═══════════════════════════════════════════════════════════
    // Google OAuth
    // ═══════════════════════════════════════════════════════════
    #[ORM\Column(name: "google_id", type: "string", length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\OneToMany(targetEntity: Oeuvre::class, mappedBy: 'artiste')]
    private Collection $oeuvres;

    #[ORM\ManyToMany(targetEntity: Galerie::class, mappedBy: 'artistes')]
    private Collection $galeries;

    #[ORM\OneToMany(targetEntity: Formation::class, mappedBy: 'user')]
    private Collection $formations;

    #[ORM\OneToMany(targetEntity: Evaluation::class, mappedBy: 'user')]
    private Collection $evaluations;

    public function __construct()
    {
        $this->oeuvres = new ArrayCollection();
        $this->galeries = new ArrayCollection();
        $this->formations = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
    }

    // ═══════════════════════════════════════════════════════════
    // Getters & Setters
    // ═══════════════════════════════════════════════════════════

    public function getId(): ?int { return $this->id; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): static { $this->prenom = $prenom; return $this; }

    public function getPassword(): ?string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(string $telephone): static { $this->telephone = $telephone; return $this; }

    public function getRole(): array { return $this->role; }

    public function setRole(array|string $role): static
    {
        $this->role = is_string($role) ? [$role] : $role;
        return $this;
    }

    public function getAvatarFilename(): ?string { return $this->avatarFilename; }
    public function setAvatarFilename(?string $avatarFilename): static { $this->avatarFilename = $avatarFilename; return $this; }

    /**
     * Retourne l'URL de l'avatar ou un avatar généré automatiquement
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatarFilename) {
            return '/uploads/avatars/' . $this->avatarFilename;
        }
        // Avatar généré via UI Avatars API (sans bundle externe)
        $name = urlencode($this->prenom . ' ' . $this->nom);
        return "https://ui-avatars.com/api/?name={$name}&background=d4a574&color=fff&size=200&bold=true";
    }

    public function getResetToken(): ?string { return $this->resetToken; }
    public function setResetToken(?string $resetToken): static { $this->resetToken = $resetToken; return $this; }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface { return $this->resetTokenExpiresAt; }
    public function setResetTokenExpiresAt(?\DateTimeInterface $dt): static { $this->resetTokenExpiresAt = $dt; return $this; }

    public function isResetTokenValid(): bool
    {
        return $this->resetToken !== null 
            && $this->resetTokenExpiresAt !== null 
            && $this->resetTokenExpiresAt > new \DateTime();
    }

    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $googleId): static { $this->googleId = $googleId; return $this; }

    /**
     * @return Collection<int, Oeuvre>
     */
    public function getOeuvres(): Collection { return $this->oeuvres; }

    public function addOeuvre(Oeuvre $oeuvre): static
    {
        if (!$this->oeuvres->contains($oeuvre)) {
            $this->oeuvres->add($oeuvre);
            $oeuvre->setArtiste($this);
        }
        return $this;
    }

    public function removeOeuvre(Oeuvre $oeuvre): static
    {
        if ($this->oeuvres->removeElement($oeuvre)) {
            if ($oeuvre->getArtiste() === $this) {
                $oeuvre->setArtiste(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Galerie>
     */
    public function getGaleries(): Collection { return $this->galeries; }

    public function addGalerie(Galerie $galerie): static
    {
        if (!$this->galeries->contains($galerie)) {
            $this->galeries->add($galerie);
            $galerie->addArtiste($this);
        }
        return $this;
    }

    public function removeGalerie(Galerie $galerie): static
    {
        if ($this->galeries->removeElement($galerie)) {
            $galerie->removeArtiste($this);
        }
        return $this;
    }

    // ═══════════════════════════════════════════════════════════
    // UserInterface
    // ═══════════════════════════════════════════════════════════

    public function getUserIdentifier(): string { return $this->email ?? ''; }

    public function getRoles(): array
    {
        $roles = $this->role;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function eraseCredentials(): void {}

    public function getFullName(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    /** @return Collection<int, Formation> */
    public function getFormations(): Collection { return $this->formations; }

    /** @return Collection<int, Evaluation> */
    public function getEvaluations(): Collection { return $this->evaluations; }
}
