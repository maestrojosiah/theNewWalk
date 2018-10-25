<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\DiscussionC;
use AppBundle\Form\DiscussionCType;

class DiscussionCController extends Controller
{

    /**
     * @Route("/discussion/comment/create", name="discussion_create_comment")
     */
    public function createAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $discussionId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $discussion = $em->getRepository('AppBundle:Discussion')
        	->find($discussionId);
        $comment = new DiscussionC();
        $comment->setBody($commentText);
        $comment->setUser($user);
        $comment->setDiscussion($discussion);

        $snipp = substr($discussion->getTitle(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $comment->setCreated(new \DateTime($today));
		$formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($comment);
        $em->flush();

        $commentId = $comment->getId();

        $notifLink = $this->generateUrl('show_discussion', array('id' => $discussionId )) . "#comment$commentId";

        // notify owner of discussion if the owner is not the one commenting
        if($discussion->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notif = $manager->generateNotification('New comment!');
            $notif->setMessage($user->getFirstName().' commented on your discussion post: "'. $snipp . '"...');
            $notif->setLink($notifLink);
            $manager->addNotification($discussion->getUser(), $notif);
        }

        // notify others who commented on the discussion
        $commentsForThisDiscussion = $discussion->getComments();
        $usersWhoCommented = [];

        // add to the array all users who commented to this discussion
        foreach($commentsForThisDiscussion as $singleComment){
            $usersWhoCommented[] = $singleComment->getUser();
        }

        // remove duplicates from array in case someone commented more than once
        $allUsersWhoCommented = array_unique($usersWhoCommented, SORT_REGULAR);

        // send them notifications
        foreach($allUsersWhoCommented as $singleUser){
            // if the user in the array is not the one commenting and is not owner of discussion
            if($singleUser != $user && $singleUser != $discussion->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also commented on this discussion post: '. $snipp . '...');
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
	 *@Route("/discussion/comment/view/{id}", name="discussion_show_comment")
	 */
	public function showCommentAction(Request $request, $id, $title = null )
	{
        //show one comment and its replies
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
		$comment = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:DiscussionC')
			->find($id);

        $discussion = $comment->getDiscussion();

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
		$data['discussion'] = $discussion;

        return $this->render('comment/discussion_comment.html.twig', $data);
	}

	/**
	 *@Route("/discussion/comment/delete/{id}", name="discussion_delete_comment")
	 */
	public function deleteCommentAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$comment = $em->getRepository('AppBundle:DiscussionC')
			->find($id);

        $discussion = $comment->getDiscussion();
        $discussionId = $discussion->getId();
            
        $replies = $comment->getReplies();
        foreach($replies as $reply){
            $em->remove($reply);
            $em->flush();
        }
		
		$em->remove($comment);
		$em->flush();	

        return $this->redirectToRoute('show_discussion', ['id' => $discussionId]);
  
	}

	/**
	 *@Route("/discussion/comment/list/{discussionId}", name="discussion_list_comments")
	 */
	public function listCommentAction(Request $request, $discussionId )
	{
        //show all comments for a specific discussion and their replies
		$data = [];
    	$em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $discussion = $em->getRepository('AppBundle:Discussion')
        	->find($discussionId);

		$comments = $em->getRepository('AppBundle:DiscussionC')
            ->findBy(
                array('discussion' => $discussion),
                array('id'=>'ASC')
            );
		$data['comments'] = $comments;
        $data['discussion'] = $discussion;
        return $this->render('comment/discussion_list.html.twig', $data);
  
	}

	/**
	 *@Route("/discussion/comment/edit", name="discussion_edit_comment")
	 */
	public function editCommentAction(Request $request )
	{
        //ajax editing of a comment
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $commentId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:DiscussionC')
            ->find($commentId);

        $comment->setBody($commentText);

        $em->persist($comment);
        $em->flush();


        $data['comment'] = $commentText;
        
        return new JsonResponse($data);
	}

}