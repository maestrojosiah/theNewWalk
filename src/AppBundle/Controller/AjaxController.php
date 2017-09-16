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


}