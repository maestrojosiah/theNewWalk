<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Post;
use AppBundle\Form\PostType;


class AjaxController extends Controller
{

    /**
     * @Route("/post/edit/get", name="get_post_for_edit")
     */
    public function getPostAction(Request $request)
    {
        if($request->request->get('id')){
            $data = [];
            $id = $request->request->get('id');
            $user = $this->get('security.token_storage')->getToken()->getUser();
      
        $em = $this->getDoctrine()->getManager();

      $post = $em->getRepository('AppBundle:Post')
        ->find($id);

      $post->setUser($user);
      
        if (!$post) {
            throw $this->createNotFoundException(
                'No post found for that title'
            );
        }

      $data['title'] = $post->getTitle();
      $data['body'] = $post->getBody();
      $data['id'] = $id;
      $data['post'] = $post;
            return new JsonResponse($data);
        }

    }

    /**
     * @Route("/article/edit/get", name="get_article_for_edit")
     */
    public function getArticleAction(Request $request)
    {
        if($request->request->get('id')){
            $data = [];
            $id = $request->request->get('id');
            $user = $this->get('security.token_storage')->getToken()->getUser();
      
        $em = $this->getDoctrine()->getManager();

      $article = $em->getRepository('AppBundle:Article')
        ->find($id);

      $article->setUser($user);
      
        if (!$article) {
            throw $this->createNotFoundException(
                'No article found for that title'
            );
        }

      $data['title'] = $article->getTitle();
      $data['body'] = $article->getBody();
      $data['id'] = $id;
      $data['article'] = $article;
            return new JsonResponse($data);
        }

    }

    /**
     * @Route("/discussion/edit/get", name="get_discussion_for_edit")
     */
    public function getDiscussionAction(Request $request)
    {
        if($request->request->get('id')){
            $data = [];
            $id = $request->request->get('id');
            $user = $this->get('security.token_storage')->getToken()->getUser();
      
        $em = $this->getDoctrine()->getManager();

      $discussion = $em->getRepository('AppBundle:Discussion')
        ->find($id);

      $discussion->setUser($user);
      
        if (!$discussion) {
            throw $this->createNotFoundException(
                'No discussion found for that title'
            );
        }

      $data['title'] = $discussion->getTitle();
      $data['body'] = $discussion->getBody();
      $data['id'] = $id;
      $data['discussion'] = $discussion;
            return new JsonResponse($data);
        }

    }

    /**
     * @Route("/lesson/edit/get", name="get_lesson_for_edit")
     */
    public function getLessonAction(Request $request)
    {
        if($request->request->get('id')){
            $data = [];
            $id = $request->request->get('id');
            $user = $this->get('security.token_storage')->getToken()->getUser();
    	
	    	$em = $this->getDoctrine()->getManager();

			$lesson = $em->getRepository('AppBundle:Lesson')
				->find($id);

			$lesson->setUser($user);
			
		    if (!$lesson) {
		        throw $this->createNotFoundException(
		            'No lesson found for that title'
		        );
		    }

			$data['title'] = $lesson->getTitle();
			$data['body'] = $lesson->getBody();
			$data['id'] = $id;
			$data['lesson'] = $lesson;
            return new JsonResponse($data);
       	}

    }

    /**
     * @Route("/get_matches/{entity}", name="get_matches")
     */
    public function getMatchesAction(Request $request, $entity)
    {
            $data = [];
            $value = $request->request->get('thisValue');

            $user = $this->get('security.token_storage')->getToken()->getUser();
            $data['user'] = $user;
            $em = $this->getDoctrine()->getManager();
            
            $Entity = ucfirst($entity);
            $posts = $this->getDoctrine()
              ->getRepository("AppBundle:$Entity")
              ->searchMatchingPosts($value);

            $linkArray = [];
            $str = [];

            if($posts){

              foreach($posts as $post){
                $linkArray[] = $this->generateUrl("show_$entity", array('id' => $post->getId() ));                
                $str[] = "<b>".$post->getTitle()."</b><br />...".substr($post->getBody(), strpos($post->getBody(), $value), 50);
              }
              
              $combined = array_combine($linkArray,  $str);
              $data['combined'] = $combined;
            }

            return new JsonResponse($data);
    }

    /**
     * @Route("/profile/edit", name="save_profile_edits")
     */
    public function profileEditAction(Request $request)
    {
            $data = [];
            $first_name = $request->request->get('first_name');
            $last_name = $request->request->get('last_name');
            $gender = $request->request->get('gender');
            $email = $request->request->get('email');

            $user = $this->get('security.token_storage')->getToken()->getUser();
            $data['user'] = $user;

            $em = $this->getDoctrine()->getManager();
            
            $user->setFirstName($first_name);
            $user->setLastName($last_name);
            $user->setEmail($email);
            $user->setGender($gender);

            $em->persist($user);
            $em->flush($user);

            
            return new JsonResponse($data);
    }




}