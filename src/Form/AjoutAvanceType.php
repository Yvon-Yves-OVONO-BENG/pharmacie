<?php

namespace App\Form;

use App\Entity\Facture;
use App\Entity\ModePaiement;
use Symfony\Component\Form\AbstractType;
use App\Repository\ModePaiementRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

class AjoutAvanceType extends AbstractType
{
    public function __construct(
        protected TranslatorInterface $translator,
        )
    {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $reste = $options['reste'];
        $builder
            ->add('avance', NumberType::class, [
                'label' => false,
                'required' => true,
                'attr' => [
                    'min' => 0,
                    'max' => $reste,
                    'class' => "form-control",
                    'placeholder' => $this->translator->trans("L'avance doit être >= 0 et <= à ").number_format($reste, 0, '', ' '),
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => $this->translator->trans("L'avance ne peut pas être vide!"),
                    ]),
                    new Assert\PositiveOrZero(),
                    new Assert\LessThanOrEqual([
                        'value' => $reste,
                        'message' => $this->translator->trans("L'avance doit être inférieure ou égale à ").number_format($reste, 0, '', ' ')." ! ",
                    ])
                ]
            ])
            ->add('modePaiement', EntityType::class, [
                'class' => ModePaiement::class,
                'choice_label' => 'modePaiement',
                'label' => false,
                'required' => true,
                'attr' => [
                    'class' => 'form-control select2-show-search',
                    'placeholder' => $this->translator->trans('- - -'),
                ],
                'query_builder' => function(ModePaiementRepository $modePaiementRepository){
                    
                    return $modePaiementRepository->createQueryBuilder('m')->orderBy('m.modePaiement');
                },
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Facture::class,
        ]);

        $resolver->setRequired('reste');
    }
}
