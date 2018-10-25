<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\DiscussionCR;
use AppBundle\Form\DiscussionCRType;

class DiscussionCRController extends Controller
{
    /**
     * @Route("discussion/comment/reply/create", name="discussion_create_comment_reply")
     */
    public function replyAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentReplyText = $request->request->get('commentReply');
        $replyId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:DiscussionC')
            ->find($replyId);

        $commentId = $comment->getId();

        $discussion = $comment->getDiscussion();
            
        $commentReply = new DiscussionCR();
        $commentReply->setBody($commentReplyText);
        $commentReply->setUser($user);
        $commentReply->setDiscCcomment($comment);

        $snipp = substr($comment->getBody(), 0, 30);
        $snippPost = substr($discussion->getTitle(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $commentReply->setCreated(new \DateTime($today));
        $formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($commentReply);
        $em->flush();

        $replyId = $commentReply->getId();
        $notifLink = $this->generateUrl('discussion_show_comment', array('id' => $commentId )) . "#reply$replyId";

        // Notify owner of comment if owner is not the one commenting
        if($user != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notifComment = $manager->generateNotification('New reply!');
            $notifComment->setMessage($user->getFirstName().' replied to your discussion comment: "'. $snipp . '"...');
            $notifComment->setLink($notifLink);
            $manager->addNotification($comment->getUser(), $notifComment);
        }
    
        // Notify owner of discussion if owner is not the one commenting and owner of discussion is not owner of comment
        if($discussion->getUser() != $user && $discussion->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notifPost = $manager->generateNotification('New reply!');
            $notifPost->setMessage($user->getFirstName().' replied to a comment on your discussion: "'. $snippPost . '"...');
            $notifPost->setLink($notifLink);
            $manager->addNotification($discussion->getUser(), $notifPost);
        }
        
        // notify others who commented on the discussion
        $repliesForThisComment = $comment->getReplies();
        $usersWhoReplied = [];

        // add to the array all users who commented to this discussion
        foreach($repliesForThisComment as $singleReply){
            $usersWhoReplied[] = $singleReply->getUser();
        }
        // remove duplicates from array in case someone commented more than once
        $allUsersWhoReplied = array_unique($usersWhoReplied, SORT_REGULAR);
        // send them notifications
        foreach($allUsersWhoReplied as $singleUser){
            // if the user in the array is not the one commenting and is not owner of discussion
            if($singleUser != $user && $singleUser != $discussion->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also replied to this discussion comment: '. $snipp . '...');
                $notif->setLink($notifLink);
                $manager->addNotification($singleUser, $notif);
            }
        }

        $data['commentReply'] = '
                            <div class="commentText">
                                <p id="replyBody_'.$replyId.'" class="">'.$commentReplyText.'</p> 
                                <button type="button" style="display:none;" id="updateReply_'.$replyId.'" class="btn btn-default">Done</button>
                                <span class="date sub-text">
                                    You, on ' .$formatedDate. '
                                    | <a style="cursor: pointer;" class="text-muted" id="deleteReply_'.$replyId.'">
                                      Delete
                                    </a>
                                    | <a style="cursor: pointer;" class="text-muted" id="editReply_'.$replyId.'">
                                      Edit
                                    </a>
                                </span>
                            </div>
                        </li>
                    ';

        return new JsonResponse($data);
    }


    /**
     *@Route("discussion/reply/delete", name="discussion_delete_reply")
     */
    public function deleteReplyAction(Request $request )
    {
        //ajax editing of a reply
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $replyId = $request->request->get('id');


        $reply = $em->getRepository('AppBundle:DiscussionCR')
            ->find($replyId);


        $em->remove($reply);
        $em->flush();


        $data['reply'] = "";
        
        return new JsonResponse($data);

    }

    /**
     *@Route("discussion/reply/list/{commentId}", name="discussion_list_replies")
     */
    public function listReplyAction(Request $request, $commentId )
    {
        //show all replies for a specific comment and their replies
        $data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $comment = $em->getRepository('AppBundle:DiscussionC')
            ->find($commentId);

        $replies = $em->getRepository('AppBundle:DiscussionCR')
            ->findBy(
                array('comment' => $comment),
                array('id'=>'ASC')
            );
        $discussion = $comment->getDiscussion();
        $data['discussion'] = $discussion;
        $data['replies'] = $replies;
        $data['comment'] = $comment;
        return $this->render('reply/list.html.twig', $data);
  
    }

    /**
     *@Route("discussion/reply/edit", name="discussion_edit_reply")
     */
    public function editReplyAction(Request $request )
    {
        //ajax editing of a reply
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $replyText = $request->request->get('reply');
        $replyId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $reply = $em->getRepository('AppBundle:DiscussionCR')
            ->find($replyId);

        $reply->setBody($replyText);

        $em->persist($reply);
        $em->flush();


        $data['reply'] = $replyText;
        
        return new JsonResponse($data);
    }


}