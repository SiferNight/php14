<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    /**
     * @var Collection<int, Dish>
     */
    #[ORM\ManyToMany(targetEntity: Dish::class, inversedBy: 'orders')]
    #[ORM\JoinTable(name: 'order_dish')]
    #[Assert\Count(min: 1, minMessage: "Заказ должен содержать хотя бы одно блюдо")]
    private Collection $dishes;

    /**
     * @var Collection<int, File>
     */
    #[ORM\ManyToMany(targetEntity: File::class, inversedBy: 'orders')]
    #[ORM\JoinTable(name: 'order_files')]
    private Collection $files;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return Collection<int, Dish>
     */
    public function getDishes(): Collection
    {
        return $this->dishes;
    }

    public function addDish(Dish $dish): static
    {
        if (!$this->dishes->contains($dish)) {
            $this->dishes->add($dish);
        }
        return $this;
    }

    public function removeDish(Dish $dish): static
    {
        $this->dishes->removeElement($dish);
        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
        }
        return $this;
    }

    public function removeFile(File $file): static
    {
        $this->files->removeElement($file);
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTotalPrice(): float
    {
        $total = 0.0;
        foreach ($this->dishes as $dish) {
            if ($dish && $dish->getPrice()) {
                $total += (float) $dish->getPrice();
            }
        }
        return $total;
    }

    public function getTotalPriceFormatted(): string
    {
        return number_format($this->getTotalPrice(), 2, '.', ' ');
    }
}