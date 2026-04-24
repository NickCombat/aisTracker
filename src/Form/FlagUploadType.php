<?php
// src/Form/FlagUploadType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

class FlagUploadType extends AbstractType
{

    public function buildForm( FormBuilderInterface $builder, array $options ): void
    {
        $builder->add( 'flag', FileType::class,
            [ 'label'       => 'Flagge hochladen',
              'mapped'      => false,
              'required'    => true,
              'constraints' => [
                  new File( ['maxSize'          => '2M',
                             'mimeTypes'        => [ 'image/png',
                                                     'image/jpeg',
                                                     'image/svg+xml',
                             ],
                             'mimeTypesMessage' => 'Bitte lade eine gültige Bilddatei hoch (PNG, JPG, SVG).',
              ] )
              ],
        ] );
    }
}
