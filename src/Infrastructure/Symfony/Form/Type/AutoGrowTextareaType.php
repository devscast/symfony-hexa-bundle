<?php

declare(strict_types=1);

namespace Devscast\Bundle\HexaBundle\Infrastructure\Symfony\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * class AutoGrowTextareaType.
 *
 * @author bernard-ng <bernard@devscast.tech>
 */
class AutoGrowTextareaType extends TextareaType
{
    public function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'attr' => [
                'is' => 'app-textarea-autogrow',
                'row' => 20,
            ],
        ]);

        return $resolver;
    }
}
