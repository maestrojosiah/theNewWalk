<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Discussion;
use AppBundle\Form\DiscussionType;

class DiscussionController extends Controller
{
    /**
     * @Route("discussion/home/{page}", defaults={"page" = 1}, name="discussion_homepage")
     */
    public function indexAction(Request $request, $page = 1 )
    {
        $data = [];

        $limit = 5;
        $offset = $page * $limit - $limit;

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        if($user != 'anon.'){
            $data['user'] = $user;

            $discussion = new Discussion();

            $discussion->setUser($user);

            $today = date("d-m-Y h:i:s");
            $discussion->setCreated(new \DateTime($today));

            $form = $this->createForm(DiscussionType::class, $discussion);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($discussion);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('discussion_homepage');
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }

        $discussions = $em->getRepository('AppBundle:Discussion')
            ->findBy(
                array(),
                array('id'=>'DESC'),
                $limit,
                $offset
            );

        if($discussions){
            $data['nextPage'] = $page + 1;
        } else {
            $data['nextPage'] = "blank";
        }
        $existingProfilePic = $em->getRepository('AppBundle:Photo')
                ->findBy(
                    array('user' => $user, 'profile' => true ),
                    array('id'=>'DESC')
                );

        if($existingProfilePic){
            $data['profile_pic'] = true;
            $data['profPic'] = $existingProfilePic[0]->getFilename();
        } else {
            $data['profile_pic'] = false;
        }

        $data['discussions'] = $discussions;



        return $this->render('discussion/home.html.twig', $data);
    }

    /**
     * @Route("/discussion/create", name="create_discussion")
     */
    public function createAction(Request $request, $page = 1 )
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

            $data['user'] = $user;

            $discussion = new Discussion();

            $discussion->setUser($user);

            $today = date("d-m-Y h:i:s");
            $discussion->setCreated(new \DateTime($today));

            $form = $this->createForm(DiscussionType::class, $discussion);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($discussion);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('homepage');
                
            }

            $data['form'] = $form->createView();


        return $this->render('discussion/create.html.twig', $data);
    }

	/**
	 *@Route("/discussion/view/{id}/{title}", defaults={"title" = ""}, name="show_discussion")
	 */
	public function showDiscussionAction(Request $request, $id, $title = null )
	{
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        if($user != 'anon.'){
            $data['user'] = $user;

            $discussion = new Discussion();

            $discussion->setUser($user);

            $today = date("d-m-Y h:i:s");
            $discussion->setCreated(new \DateTime($today));

            $form = $this->createForm(DiscussionType::class, $discussion);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($discussion);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('homepage');
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }
		$discussion = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:Discussion')
			->find($id);

        $existingProfilePic = $em->getRepository('AppBundle:Photo')
                ->findBy(
                    array('user' => $user, 'profile' => true ),
                    array('id'=>'DESC')
                );

	    if (!$discussion) {
	        throw $this->createNotFoundException(
	            'No discussion found for that title'
	        );
	    }
            
        if($existingProfilePic){
            $data['profile_pic'] = true;
            $data['profPic'] = $existingProfilePic[0]->getFilename();
        } else {
            $data['profile_pic'] = false;
        }
            
		$data['discussion'] = $discussion;

        return $this->render('discussion/discussion.html.twig', $data);
	}

	/**
	 *@Route("/discussion/delete/{id}", name="delete_discussion")
	 */
	public function deleteDiscussionAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$discussion = $em->getRepository('AppBundle:Discussion')
			->find($id);

        $comments = $discussion->getComments();

        foreach($comments as $comment){
            $replies = $comment->getReplies();
            foreach($replies as $reply){
                $em->remove($reply);
                $em->flush();
            }
            $em->remove($comment);
            $em->flush();
        }
		
		$em->remove($discussion);
		$em->flush();	

        return $this->redirectToRoute('homepage');
  
	}

	/**
	 *@Route("/discussion/edit/{id}", name="edit_discussion")
	 */
	public function editDiscussionAction(Request $request, $id )
	{
		$data = [];

    	$em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $data['user'] = $user;

		$discussion = $em->getRepository('AppBundle:Discussion')
			->find($id);
		
	    if (!$discussion) {
	        throw $this->createNotFoundException(
	            'No discussion found for that title'
	        );
	    }
        $form = $this->createForm(DiscussionType::class, $discussion);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($discussion);
            $em->flush();

            $this->addFlash(
                'success',
                'Thank you for sharing!'
            );

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('show_discussion', ['id' => $id]);
            
        } else {
        	$form_data['title'] = $discussion->getTitle();
        	$form_data['body'] = $discussion->getBody();
        }

        $data['form_data'] = $form_data;
        $data['form'] = $form->createView();

		$data['discussion'] = $discussion;

        return $this->render('discussion/edit.html.twig', $data);
	}

}