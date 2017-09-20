<?php 

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ivory\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;

class ArticleType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$article = $options['data'];
		$user = $article->getUser();
		$folder = $user->getFirstName().$user->getLastName();		
		$builder
			->add('title', TextType::class)
			->add('description', TextType::class)
			->add('body', CKEditorType::class, array(
			    'config' => array(
			        'uiColor' => '#ffffff',
			        'uploadUrl' => 'http://www.thenewwalk.org/public',
			    ),
			))
			->add('save', SubmitType::class,  array('label' => 'Send Article'))
		;
	}

	public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Article',
        ));
    }

}