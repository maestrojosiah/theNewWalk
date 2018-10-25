<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\ArticleC;
use AppBundle\Form\ArticleCType;

class ArticleCController extends Controller
{

    /**
     * @Route("/article/comment/create", name="article_create_comment")
     */
    public function createAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $articleId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $article = $em->getRepository('AppBundle:Article')
        	->find($articleId);
        $comment = new ArticleC();
        $comment->setBody($commentText);
        $comment->setUser($user);
        $comment->setArticle($article);

        $snipp = substr($article->getTitle(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $comment->setCreated(new \DateTime($today));
		$formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($comment);
        $em->flush();

        $commentId = $comment->getId();

        $notifLink = $this->generateUrl('show_article', array('id' => $articleId )) . "#comment$commentId";

        // notify owner of article if the owner is not the one commenting
        if($article->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notif = $manager->generateNotification('New comment!');
            $notif->setMessage($user->getFirstName().' commented on your article: "'. $snipp . '"...');
            $notif->setLink($notifLink);
            $manager->addNotification($article->getUser(), $notif);
        }

        // notify others who commented on the article
        $commentsForThisArticle = $article->getComments();
        $usersWhoCommented = [];

        // add to the array all users who commented to this article
        foreach($commentsForThisArticle as $singleComment){
            $usersWhoCommented[] = $singleComment->getUser();
        }

        // remove duplicates from array in case someone commented more than once
        $allUsersWhoCommented = array_unique($usersWhoCommented, SORT_REGULAR);

        // send them notifications
        foreach($allUsersWhoCommented as $singleUser){
            // if the user in the array is not the one commenting and is not owner of article
            if($singleUser != $user && $singleUser != $article->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also commented on this article: '. $snipp . '...');
                $notif->setLink($notifLink);
                $manager->addNotification($singleUser, $notif);
            }
        }

		$data['comment'] = '
						<div class="commentBox">
			               <div class="panel panel-default">
			                  <div class="panel-heading">
			                      <strong>'.$user->getFirstName().'</strong> <span class="text-muted">commented on ' . $formatedDate . '</span>			                  
                              | <span style="cursor: pointer;" class="text-muted sm-text" id="editComment_'.$commentId.'">
                                Edit
                              </span>
                          </div>
			                  <div class="panel-body">
			                      <p id="commentBody_'.$commentId.'">'.$commentText.'</p>
                                  <button type="button" style="display:none;" id="updateComment_'.$commentId.'" class="btn btn-default">Done</button>
			                  </div>
			              </div>
			            </div>
                 ';
        
	    return new JsonResponse($data);
    }

	/**
	 *@Route("/article/comment/view/{id}", name="article_show_comment")
	 */
	public function showCommentAction(Request $request, $id, $title = null )
	{
        //show one comment and its replies
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
		$comment = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:ArticleC')
			->find($id);

        $article = $comment->getArticle();

	    if (!$comment) {
	        throw $this->createNotFoundException(
	            'No comment found for that title'
	        );
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
            

        $data['comment'] = $comment;
		$data['article'] = $article;

        return $this->render('comment/article_comment.html.twig', $data);
	}

	/**
	 *@Route("/article/comment/delete/{id}", name="article_delete_comment")
	 */
	public function deleteCommentAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$comment = $em->getRepository('AppBundle:ArticleC')
			->find($id);

        $article = $comment->getArticle();
        $articleId = $article->getId();
            
        $replies = $comment->getReplies();
        foreach($replies as $reply){
            $em->remove($reply);
            $em->flush();
        }
		
		$em->remove($comment);
		$em->flush();	

        return $this->redirectToRoute('show_article', ['id' => $articleId]);
  
	}

	/**
	 *@Route("/article/comment/list/{articleId}", name="article_list_comments")
	 */
	public function listCommentAction(Request $request, $articleId )
	{
        //show all comments for a specific article and their replies
		$data = [];
    	$em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $article = $em->getRepository('AppBundle:Article')
        	->find($articleId);

		$comments = $em->getRepository('AppBundle:ArticleC')
            ->findBy(
                array('article' => $article),
                array('id'=>'ASC')
            );
		$data['comments'] = $comments;
        $data['article'] = $article;
        return $this->render('comment/article_list.html.twig', $data);
  
	}

	/**
	 *@Route("/article/comment/edit", name="article_edit_comment")
	 */
	public function editCommentAction(Request $request )
	{
        //ajax editing of a comment
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $commentId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:ArticleC')
            ->find($commentId);

        $comment->setBody($commentText);

        $em->persist($comment);
        $em->flush();


        $data['comment'] = $commentText;
        
        return new JsonResponse($data);
	}

    /**
     * @Route("/makeup/article_c", name="makeup_articles")
     */
    public function articleMakeupAction(Request $request)
    {
        set_time_limit(0);
        $articleArray = 
array (
  0 => 
  array (
    'article_id' => '5',
    'author' => 'maestrojosiah@gmail.com',
    'created' => '2014-04-15 21:15:45',
    'body' => 'If every youth knew this! No wonder God says that we perish because of lack of knowledge.',
  ),
  1 => 
  array (
    'article_id' => '4',
    'author' => 'maestrojosiah@gmail.com',
    'created' => '2014-04-15 21:32:35',
    'body' => 'May God help us!',
  ),
  2 => 
  array (
    'article_id' => '3',
    'author' => 'jyn1471@gmail.com',
    'created' => '2014-04-17 16:25:54',
    'body' => 'if only people would help every1 realize that the devil is not young.. He know what music can do from exprience',
  ),

  );
        // 1) build the form
        foreach($articleArray as $articleSingle){
            $article = new ArticleC();

            $user = $this->getDoctrine()->getManager()
                ->getRepository('AppBundle:User')
                ->findOneByEmail($articleSingle['author']);

            $article_c = $this->getDoctrine()->getManager()
                ->getRepository('AppBundle:Article')
                ->find($articleSingle['article_id']);

            $article->setArticle($article_c);
            $article->setUser($user);
            $article->setBody($articleSingle['body']);
            $article->setCreated(new \DateTime($articleSingle['created']));

            // 4) save the Article!
            $em = $this->getDoctrine()->getManager();
            $em->persist($article);
            $em->flush();
        }
        
        $data['articleArray'] = $user;
        return $this->render('registration/makeup.html.twig', ['data'=>$data]  );
    }


}