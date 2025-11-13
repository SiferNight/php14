<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Client;
use App\Entity\Dish;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => function(Client $client) {
                    return $client->getFIO() . ' - ' . $client->getPhone();
                },
                'placeholder' => 'Выберите клиента',
                'label' => 'Клиент',
                'constraints' => [new \Symfony\Component\Validator\Constraints\NotBlank()],
                'attr' => ['class' => 'form-select']
            ])
            ->add('dishes', EntityType::class, [
                'class' => Dish::class,
                'choice_label' => function(Dish $dish) {
                    return $dish->getName() . ' - ' . $dish->getPrice() . ' руб.';
                },
                'multiple' => true,
                'expanded' => true,
                'label' => 'Блюда',
                'constraints' => [
                    new Count([
                        'min' => 1,
                        'minMessage' => 'Заказ должен содержать как минимум одно блюдо.',
                    ]),
                ],
            ])
            ->add('fileUploads', FileType::class, [
                'label' => 'Прикрепить файлы (PDF, DOCX, TXT, JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'multiple' => true,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}