<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\PhotoC;
use AppBundle\Form\PhotoCType;

class PhotoCController extends Controller
{

    /**
     * @Route("/photo/comment/create", name="photo_create_comment")
     */
    public function createAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $photoId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $photo = $em->getRepository('AppBundle:Photo')
        	->find($photoId);
        $comment = new PhotoC();
        $comment->setBody($commentText);
        $comment->setUser($user);
        $comment->setPhoto($photo);

        $snipp = substr($photo->getCaption(), 0, 30);

        $today = date("d-m-Y h:i:s");
        $formatedDate = new \DateTime($today);
        $comment->setCreated(new \DateTime($today));
		$formatedDate = $formatedDate->format('F jS, Y'); // for example

        $em->persist($comment);
        $em->flush();

        $commentId = $comment->getId();

        $notifLink = $this->generateUrl('view_photo', array('id' => $photoId )) . "#comment$commentId";

        // notify owner of photo if the owner is not the one commenting
        if($photo->getUser() != $comment->getUser()){
            $manager = $this->get('mgilet.notification');
            $notif = $manager->generateNotification('New comment!');
            $notif->setMessage($user->getFirstName().' commented on your photo: "'. $snipp . '"...');
            $notif->setLink($notifLink);
            $manager->addNotification($photo->getUser(), $notif);
        }

        // notify others who commented on the photo
        $commentsForThisPhoto = $photo->getComments();
        $usersWhoCommented = [];

        // add to the array all users who commented to this photo
        foreach($commentsForThisPhoto as $singleComment){
            $usersWhoCommented[] = $singleComment->getUser();
        }

        // remove duplicates from array in case someone commented more than once
        $allUsersWhoCommented = array_unique($usersWhoCommented, SORT_REGULAR);

        // send them notifications
        foreach($allUsersWhoCommented as $singleUser){
            // if the user in the array is not the one commenting and is not owner of photo
            if($singleUser != $user && $singleUser != $photo->getUser()){
                $manager = $this->get('mgilet.notification');
                $notif = $manager->generateNotification('Follow up');
                $notif->setMessage($user->getFirstName().' also commented on this photo: '. $snipp . '...');
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
	 *@Route("/photo/comments/view/{id}", name="photo_show_comments")
	 */
	public function showCommentsAction(Request $request, $id, $title = null )
	{
        //show all photo comments
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;

        $photo = 
		$comment = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:PhotoC')
			->find($id);

        $photo = $comment->getPhoto();

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
		$data['photo'] = $photo;

        return $this->render('photo/view.html.twig', $data);
	}

	/**
	 *@Route("/photo/comment/delete/{id}", name="photo_delete_comment")
	 */
	public function deleteCommentAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$comment = $em->getRepository('AppBundle:PhotoC')
			->find($id);

        $photo = $comment->getPhoto();
        $photoId = $photo->getId();
            		
		$em->remove($comment);
		$em->flush();	

        return $this->redirectToRoute('view_photo', ['id' => $photoId]);
  
	}

	/**
	 *@Route("/photo/comment/list/{photoId}", name="photo_list_comments")
	 */
	public function listCommentAction(Request $request, $photoId )
	{
        //show all comments for a specific photo and their replies
		$data = [];
    	$em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;
        $photo = $em->getRepository('AppBundle:Photo')
        	->find($photoId);

		$comments = $em->getRepository('AppBundle:PhotoC')
            ->findBy(
                array('photo' => $photo),
                array('id'=>'ASC')
            );
		$data['comments'] = $comments;
        $data['photo'] = $photo;
        return $this->render('comment/photo_list.html.twig', $data);
  
	}

	/**
	 *@Route("/photo/comment/edit", name="photo_edit_comment")
	 */
	public function editCommentAction(Request $request )
	{
        //ajax editing of a comment
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $commentText = $request->request->get('comment');
        $commentId = $request->request->get('id');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        $comment = $em->getRepository('AppBundle:PhotoC')
            ->find($commentId);

        $comment->setBody($commentText);

        $em->persist($comment);
        $em->flush();


        $data['comment'] = $commentText;
        
        return new JsonResponse($data);
	}

}