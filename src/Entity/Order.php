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

    #[ORM\ManyToOne]
    private ?Client $client = null;

    /**
     * @var Collection<int, Dish>
     */
    #[ORM\ManyToMany(targetEntity: Dish::class, inversedBy: 'orders')]
    #[Assert\Count(min: 1, minMessage: "Заказ должен содержать хотя бы одно блюдо")]
    private Collection $dishes;

    #[ORM\Column(type: 'json')]
    private array $files = [];

    public function __construct()
    {
        $this->dishes = new ArrayCollection();
        $this->files = [];
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

    public function getFiles(): array
    {
        return $this->files;
    }

    public function setFiles(array $files): static
    {
        $this->files = $files;

        return $this;
    }

    public function addFile(string $filePath): static
    {
        if (!in_array($filePath, $this->files, true)) {
            $this->files[] = $filePath;
        }

        return $this;
    }

    public function removeFile(string $filePath): static
    {
        $key = array_search($filePath, $this->files, true);
        if ($key !== false) {
            unset($this->files[$key]);
            $this->files = array_values($this->files);
        }

        return $this;
    }
}