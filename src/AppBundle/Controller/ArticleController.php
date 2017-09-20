<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Article;
use AppBundle\Form\ArticleType;

class ArticleController extends Controller
{
    /**
     * @Route("article/home/{page}", defaults={"page" = 1}, name="article_homepage")
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

            $article = new Article();

            $article->setUser($user);

            $today = date("d-m-Y h:i:s");
            $article->setCreated(new \DateTime($today));

            $form = $this->createForm(ArticleType::class, $article);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($article);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('article_homepage');
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }

        $articles = $em->getRepository('AppBundle:Article')
            ->findBy(
                array(),
                array('id'=>'DESC'),
                $limit,
                $offset
            );

        if($articles){
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

        $data['articles'] = $articles;



        return $this->render('article/home.html.twig', $data);
    }

    /**
     * @Route("/article/create", name="create_article")
     */
    public function createAction(Request $request, $page = 1 )
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

            $data['user'] = $user;

            $article = new Article();

            $article->setUser($user);

            $today = date("d-m-Y h:i:s");
            $article->setCreated(new \DateTime($today));

            $form = $this->createForm(ArticleType::class, $article);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($article);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('article_homepage');
                
            }

            $data['form'] = $form->createView();


        return $this->render('article/create.html.twig', $data);
    }

	/**
	 *@Route("/article/view/{id}/{title}", defaults={"title" = ""}, name="show_article")
	 */
	public function showArticleAction(Request $request, $id, $title = null )
	{
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        if($user != 'anon.'){
            $data['user'] = $user;

            $article = new Article();

            $article->setUser($user);

            $today = date("d-m-Y h:i:s");
            $article->setCreated(new \DateTime($today));

            $form = $this->createForm(ArticleType::class, $article);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($article);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('article_homepage');
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }
		$article = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:Article')
			->find($id);

        $existingProfilePic = $em->getRepository('AppBundle:Photo')
                ->findBy(
                    array('user' => $user, 'profile' => true ),
                    array('id'=>'DESC')
                );

	    if (!$article) {
	        throw $this->createNotFoundException(
	            'No article found for that title'
	        );
	    }
            
        if($existingProfilePic){
            $data['profile_pic'] = true;
            $data['profPic'] = $existingProfilePic[0]->getFilename();
        } else {
            $data['profile_pic'] = false;
        }
            
        $likes = $em->getRepository('AppBundle:ArticleL')
            ->countLikes($article);

        $data['likes'] = $likes;
		$data['article'] = $article;

        return $this->render('article/article.html.twig', $data);
	}

	/**
	 *@Route("/article/delete/{id}", name="delete_article")
	 */
	public function deleteArticleAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$article = $em->getRepository('AppBundle:Article')
			->find($id);

        $comments = $article->getComments();

        foreach($comments as $comment){
            $replies = $comment->getReplies();
            foreach($replies as $reply){
                $em->remove($reply);
                $em->flush();
            }
            $em->remove($comment);
            $em->flush();
        }
		
		$em->remove($article);
		$em->flush();	

        return $this->redirectToRoute('article_homepage');
  
	}

	/**
	 *@Route("/article/edit/{id}", name="edit_article")
	 */
	public function editArticleAction(Request $request, $id )
	{
		$data = [];

    	$em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $data['user'] = $user;

		$article = $em->getRepository('AppBundle:Article')
			->find($id);
		
	    if (!$article) {
	        throw $this->createNotFoundException(
	            'No article found for that title'
	        );
	    }
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($article);
            $em->flush();

            $this->addFlash(
                'success',
                'Thank you for sharing!'
            );

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('show_article', ['id' => $id]);
            
        } else {
        	$form_data['title'] = $article->getTitle();
        	$form_data['body'] = $article->getBody();
        }

        $data['form_data'] = $form_data;
        $data['form'] = $form->createView();

		$data['article'] = $article;

        return $this->render('article/edit.html.twig', $data);
	}


}