<?php 

namespace MessageBundle\Form;

use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;
use FD\PrivateMessageBundle\Form\ConversationType as BaseType;


// Make your form extends FDPrivateMessageBunde::ConversationType.
class ConversationType extends BaseType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Call parent's builder.
        parent::buildForm($builder, $options);


        // Load only users being enabled.
        $builder->add('recipients', EntityType::class, array(
            'class'         => 'AppBundle:ASDd',
            'multiple'      => true,
            'query_builder' => function(EntityRepository $er) {
                return $er->createQueryBuilder('u')
                    ->where('u.id', $id);
            },
        ));

    }
}