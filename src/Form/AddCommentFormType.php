<?php

namespace App\Form;

use App\Entity\Comment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddCommentFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('content', TextareaType::class, [
                "label"=>false,
                "attr"=>[
                    "rows"=>10,
                    "placeholder"=>"Laisser votre commentaire ...",
                ],
                "purify_html"=>true,
                "constraints"=>[
                    new NotBlank([
                        "message"=>"Merci de renseigner un contenu !"
                    ]),
                    new Length([
                        "min"=>2,
                        "minMessage"=>"Le commentaire doit contenir au moins {{ limit }} caractères !",
                        "max"=>8000,
                        "maxMessage"=>"Le commentaire doit contenir au maximum {{ limit }} caractères !",
                    ]),
                ],
            ])
            ->add("save", SubmitType::class, [
                "label"=>"Publier",
                "attr"=>[
                    "class"=>"btn btn-outline-primary w-100"
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Comment::class,
            // TODO: virer le novalidate
            "attr"=>[
                "novalidate"=>"novalidate",
            ],
        ]);
    }
}
