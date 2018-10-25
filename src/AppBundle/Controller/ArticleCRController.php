<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\ArticleCR;
use AppBundle\Form\ArticleCRType;

class ArticleCRController extends Controller
{
    /**
     * @Route("article/comment/reply/create", name="article_create_comment_reply")
     */
    public function replyAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentReplyText = $request->request->get('commentReply');
        $replyId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:ArticleC')
            ->find($replyId);

        $commentId = $comment->getId();

        $article = $comment->getArticle();
            
        $commentReply = new ArticleCR();
        $commentReply->setBody($commentReplyText);
        $commentReply->setUser($user);
        $commentReply->setComment($comment);

        $snipp = substr($comment->getBody(), 0, 30);
        $snippPost = substr($article->getTitle(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $commentReply->setCreated(new \DateTime($today));
        $formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($commentReply);
        $em->flush();

        $replyId = $commentReply->getId();
        $notifLink = $this->generateUrl('article_show_comment', array('id' => $commentId )) . "#reply$replyId";

        // Notify owner of comment if owner is not the one commenting
        if($user != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notifComment = $manager->generateNotification('New reply!');
            $notifComment->setMessage($user->getFirstName().' replied to your article comment: "'. $snipp . '"...');
            $notifComment->setLink($notifLink);
            $manager->addNotification($comment->getUser(), $notifComment);
        }
    
        // Notify owner of article if owner is not the one commenting and owner of article is not owner of comment
        if($article->getUser() != $user && $article->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notifPost = $manager->generateNotification('New reply!');
            $notifPost->setMessage($user->getFirstName().' replied to a comment on your article: "'. $snippPost . '"...');
            $notifPost->setLink($notifLink);
            $manager->addNotification($article->getUser(), $notifPost);
        }
        
        // notify others who commented on the article
        $repliesForThisComment = $comment->getReplies();
        $usersWhoReplied = [];

        // add to the array all users who commented to this article
        foreach($repliesForThisComment as $singleReply){
            $usersWhoReplied[] = $singleReply->getUser();
        }
        // remove duplicates from array in case someone commented more than once
        $allUsersWhoReplied = array_unique($usersWhoReplied, SORT_REGULAR);
        // send them notifications
        foreach($allUsersWhoReplied as $singleUser){
            // if the user in the array is not the one commenting and is not owner of article
            if($singleUser != $user && $singleUser != $article->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also replied to this article comment: '. $snipp . '...');
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
     *@Route("article/reply/delete", name="article_delete_reply")
     */
    public function deleteReplyAction(Request $request )
    {
        //ajax editing of a reply
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $replyId = $request->request->get('id');


        $reply = $em->getRepository('AppBundle:ArticleCR')
            ->find($replyId);


        $em->remove($reply);
        $em->flush();


        $data['reply'] = "";
        
        return new JsonResponse($data);

    }

    /**
     *@Route("article/reply/list/{commentId}", name="article_list_replies")
     */
    public function listReplyAction(Request $request, $commentId )
    {
        //show all replies for a specific comment and their replies
        $data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $comment = $em->getRepository('AppBundle:ArticleC')
            ->find($commentId);

        $replies = $em->getRepository('AppBundle:ArticleCR')
            ->findBy(
                array('comment' => $comment),
                array('id'=>'ASC')
            );
        $article = $comment->getArticle();
        $data['article'] = $article;
        $data['replies'] = $replies;
        $data['comment'] = $comment;
        return $this->render('reply/list.html.twig', $data);
  
    }

    /**
     *@Route("article/reply/edit", name="article_edit_reply")
     */
    public function editReplyAction(Request $request )
    {
        //ajax editing of a reply
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $replyText = $request->request->get('reply');
        $replyId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $reply = $em->getRepository('AppBundle:ArticleCR')
            ->find($replyId);

        $reply->setBody($replyText);

        $em->persist($reply);
        $em->flush();


        $data['reply'] = $replyText;
        
        return new JsonResponse($data);
    }


}