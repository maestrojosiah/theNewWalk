<?php 

namespace AppBundle\Controller;

use AppBundle\Form\UserType;
use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class RegistrationController extends Controller
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function registerAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        // 1) build the form
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        // 2) handle the submit (will only happen on POST)
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $user->getPlainPassword());
            $user->setPassword($password);

            //generate a random unique code for authentication
            $byte_code = random_bytes(12);
            $code_first = base64_encode($byte_code);
            $code = str_replace("/", "g", $code_first);
            $user->setRandomAuth($code);

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('login');
        }

        return $this->render(
            'registration/register.html.twig',
            array('user' => ['firstName'=>'anonymous', 'action'=>'register'], 'form' => $form->createView())
        );
    }


    /**
     * @Route("/makeup/register", name="makeup_registration")
     */
    public function registerMakeupAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $userArray =
array (
  316 => 
  array (
    'first_name' => 'Josephine',
    'last_name' => ' Jemutai',
    'email' => 'josphinejemutai2@gmail.com',
    'password' => '18041293',
    'gender' => 'Female',
  ),
  317 => 
  array (
    'first_name' => 'Allan',
    'last_name' => 'Mogusu ',
    'email' => 'allannelly690@gmail.com',
    'password' => 'allannyabayo069',
    'gender' => 'Male',
  ),
  318 => 
  array (
    'first_name' => 'Allan',
    'last_name' => 'Mogusu ',
    'email' => 'allannelly@rocketmail.com',
    'password' => '2532',
    'gender' => 'Male',
  ),
);        
        // 1) build the form
        foreach($userArray as $userSingle){
            $user = new User();
            $password = $passwordEncoder->encodePassword($user, $userSingle['password']);
            $user->setPassword($password);

            //generate a random unique code for authentication
            $byte_code = random_bytes(12);
            $code_first = base64_encode($byte_code);
            $code = str_replace("/", "g", $code_first);
            $user->setRandomAuth($code);

            $user->setFirstName($userSingle['first_name']);
            $user->setLastName($userSingle['last_name']);
            $user->setEmail($userSingle['email']);
            $user->setGender($userSingle['gender']);
            $user->setIsAdmin(false);
            $user->setActive(true);

            // 4) save the User!
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
        }
        
        $data['userArray'] = $userArray;
        return $this->render('registration/makeup.html.twig', $data  );
    }
}
