<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Comment;
use AppBundle\Form\CommentType;

class CommentController extends Controller
{

    /**
     * @Route("/comment/create", name="create_comment")
     */
    public function createAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $postId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $post = $em->getRepository('AppBundle:Post')
        	->find($postId);
        $comment = new Comment();
        $comment->setBody($commentText);
        $comment->setUser($user);
        $comment->setPost($post);

        $snipp = substr($post->getTitle(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $comment->setCreated(new \DateTime($today));
		$formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($comment);
        $em->flush();

        $commentId = $comment->getId();

        $notifLink = $this->generateUrl('show_post', array('id' => $postId )) . "#comment$commentId";

        // notify owner of post if the owner is not the one commenting
        if($post->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notif = $manager->generateNotification('New comment!');
            $notif->setMessage($user->getFirstName().' commented on your post: "'. $snipp . '"...');
            $notif->setLink($notifLink);
            $manager->addNotification($post->getUser(), $notif);
        }

        // notify others who commented on the post
        $commentsForThisPost = $post->getComments();
        $usersWhoCommented = [];

        // add to the array all users who commented to this post
        foreach($commentsForThisPost as $singleComment){
            $usersWhoCommented[] = $singleComment->getUser();
        }

        // remove duplicates from array in case someone commented more than once
        $allUsersWhoCommented = array_unique($usersWhoCommented, SORT_REGULAR);

        // send them notifications
        foreach($allUsersWhoCommented as $singleUser){
            // if the user in the array is not the one commenting and is not owner of post
            if($singleUser != $user && $singleUser != $post->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also commented on this post: '. $snipp . '...');
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
	 *@Route("/comment/view/{id}", name="show_comment")
	 */
	public function showCommentAction(Request $request, $id, $title = null )
	{
        //show one comment and its replies
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
		$comment = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:Comment')
			->find($id);

        $post = $comment->getPost();

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
		$data['post'] = $post;

        return $this->render('comment/comment.html.twig', $data);
	}

	/**
	 *@Route("/comment/delete/{id}", name="delete_comment")
	 */
	public function deleteCommentAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$comment = $em->getRepository('AppBundle:Comment')
			->find($id);

        $post = $comment->getPost();
        $postId = $post->getId();
            
        $replies = $comment->getReplies();
        foreach($replies as $reply){
            $em->remove($reply);
            $em->flush();
        }
		
		$em->remove($comment);
		$em->flush();	

        return $this->redirectToRoute('show_post', ['id' => $postId]);
  
	}

	/**
	 *@Route("/comment/list/{postId}", name="list_comments")
	 */
	public function listCommentAction(Request $request, $postId )
	{
        //show all comments for a specific post and their replies
		$data = [];
    	$em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $post = $em->getRepository('AppBundle:Post')
        	->find($postId);

		$comments = $em->getRepository('AppBundle:Comment')
            ->findBy(
                array('post' => $post),
                array('id'=>'ASC')
            );
		$data['comments'] = $comments;
        $data['post'] = $post;
        return $this->render('comment/list.html.twig', $data);
  
	}

	/**
	 *@Route("/comment/edit", name="edit_comment")
	 */
	public function editCommentAction(Request $request )
	{
        //ajax editing of a comment
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $commentId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:Comment')
            ->find($commentId);

        $comment->setBody($commentText);

        $em->persist($comment);
        $em->flush();


        $data['comment'] = $commentText;
        
        return new JsonResponse($data);
	}

    /**
     * @Route("/makeup/comment", name="makeup_comments")
     */
    public function commentMakeupAction(Request $request)
    {
        set_time_limit(0);
        $commentArray = 
array (
  0 => 
  array (
    'author' => 'Kibiwottarus@gmail.com',
    'update_id' => '1379',
    'created' => '2015-05-19 12:16:01',
    'body' => 'Amen!',
  ),
  1 => 
  array (
    'author' => 'Kibiwottarus@gmail.com',
    'update_id' => '1375',
    'created' => '2015-05-19 12:28:03',
    'body' => 'It is the fulfilment of prophesy.',
  ),
  2 => 
  array (
    'author' => 'elizabethmoraa200@gmail.com',
    'update_id' => '1379',
    'created' => '2015-05-19 14:33:30',
    'body' => 'Amen!it doesn\'t matter who you are God still loves us.Amazing grace indeed and love.',
  ),
  3 => 
  array (
    'author' => 'milcahkelly6@gmail.com',
    'update_id' => '1379',
    'created' => '2015-05-19 23:06:23',
    'body' => 'Wow! Amen!',
  ),
);
        // 1) build the form
        foreach($commentArray as $commentSingle){
            $comment = new Comment();

            $user = $this->getDoctrine()->getManager()
                ->getRepository('AppBundle:User')
                ->findOneByEmail($commentSingle['author']);

            $post = $this->getDoctrine()->getManager()
                ->getRepository('AppBundle:Post')
                ->find($commentSingle['update_id']);

            $comment->setPost($post);
            $comment->setUser($user);
            $comment->setBody($commentSingle['body']);
            $comment->setCreated(new \DateTime($commentSingle['created']));

            // 4) save the Comment!
            $em = $this->getDoctrine()->getManager();
            $em->persist($comment);
            $em->flush();
        }
        
        $data['commentArray'] = $user;
        return $this->render('registration/makeup.html.twig', ['data'=>$data]  );
    }


}