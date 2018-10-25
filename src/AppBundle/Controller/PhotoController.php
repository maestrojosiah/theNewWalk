<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use AppBundle\Entity\Photo;
use AppBundle\Form\PhotoType;

class PhotoController extends Controller
{
    /**
     * @Route("/photo/upload", name="upload_photo")
     */
    public function uploadAction(Request $request)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $photo = new Photo();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;

        $form = $this->createForm(PhotoType::class, $photo);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $file = $photo->getFilename();
            $fileName = date("YmdHis")."|".$user->getRandomAuth().".".$file->guessExtension();
            $photo->setSize($file->getClientSize());
            $photo->setType($file->getMimeType());

            $file->move(
                $this->getParameter('photo_directory'),
                $fileName
            );

            $photo->setUser($user);
            $photo->setFileName($fileName);


            $em->persist($photo);
            $em->flush();

            $this->addFlash(
                'success',
                'Photo uploaded!'
            );

            // ... do any other work - like sending them an email, etc
            // maybe set a "flash" success message for the user

            return $this->redirectToRoute('user_profile', ['slug' => $user->getRandomAuth()]);
            
        }

        return $this->render('photo/create.html.twig', ['form' => $form->createView(), 'data' => $data,] );

    }

    /**
     * @Route("/photo/delete/{id}", name="delete_photo")
     */
    public function deleteAction(Request $request, $id)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $photo = $em->getRepository('AppBundle:Photo')
            ->find($id);

        $photoDirectory = $this->getParameter('photo_directory');
        $photoName = $photo->getFilename();
        $path = $photoDirectory."/".$photoName;
        unlink($path);

        $em->remove($photo);
        $em->flush();

        $this->addFlash(
            'success',
            'Photo deleted!'
        );

        // ... do any other work - like sending them an email, etc
        // maybe set a "flash" success message for the user

        return $this->redirectToRoute('user_profile', ['slug' => $user->getRandomAuth()]);

    }

    /**
     * @Route("/photo/view/{id}", name="view_photo")
     */
    public function viewAction(Request $request, $id)
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $photo = $em->getRepository('AppBundle:Photo')
            ->find($id);
        $data['photo'] = $photo;

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;

        $comments = $photo->getComments();
        if($comments){
            $data['comments'] = $comments;
        }

        return $this->render('photo/view.html.twig', $data );

    }

    /**
     * @Route("/photos/view/{user}/{page}", defaults={"page" = 1}, name="view_all_photos")
     */
    public function viewAllAction(Request $request, $user, $page)
    {
        $data = [];

        $limit = 6;
        $offset = $page * $limit - $limit;
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('AppBundle:User')
            ->find($user);


        $photos = $em->getRepository('AppBundle:Photo')
            ->findBy(
                array('user' => $user),
                array('id' => 'DESC'),
                $limit,
                $offset
                );
        $data['photos'] = $photos;

        $data['user'] = $user;

        $comments = [];
        foreach($photos as $photo){
            $comments[] = $photo->getComments();
        }

        if($comments){
            $data['comments'] = $comments;
        }

        if($photos){
            $data['nextPage'] = $page + 1;
        } else {
            $data['nextPage'] = "blank";
        }

        return $this->render('photo/all.html.twig', $data );

    }

    /**
     * @Route("/profile/make", name="make_profile_picture")
     */
    public function pictureChangeAction(Request $request)
    {
        if($request->request->get('id')){

            $em = $this->getDoctrine()->getManager();

            $user = $this->get('security.token_storage')->getToken()->getUser();
            $id = $request->request->get('id');

            $photo = $em->getRepository('AppBundle:Photo')
                ->find($id);

            $profilePhoto = $em->getRepository('AppBundle:Photo')
                ->findBy(
                    array('user' => $user, 'profile' => true ),
                    array('id'=>'DESC')
                );

            if($profilePhoto){
                $profilePhoto[0]->setProfile(false);
                $em->persist($photo);
                $em->flush();                
            }

            $photo->setProfile(true);
            $em->persist($photo);
            $em->flush();


            $data['prof'] = $photo->getFilename();
        } 
            
        return new JsonResponse($data);

    }

    /**
     * @Route("/ajax/upload", name="ajax_upload")
     */
    public function pictureAjaxUploadAction(Request $request)
    {

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $formData = $request->request->get('formData');

        $data['formData'] = $formData;   

        if(isset($_FILES['croppedImage']) and !$_FILES['croppedImage']['error']){
            $file = $_FILES['croppedImage']['tmp_name'];
            $what = getimagesize($file);
            $fileExtention = strtolower($what['mime']);

            switch(strtolower($what['mime']))
            {
                case 'image/png':
                    $img_r = imagecreatefrompng($file);
                    $source_image = imagecreatefrompng($file);
                    $type = '.png';
                    break;
                case 'image/jpeg':
                    $img_r = imagecreatefromjpeg($file);
                    $source_image = imagecreatefromjpeg($file);
                    error_log("jpg");
                    $type = '.jpeg';
                    break;
                case 'image/gif':
                    $img_r = imagecreatefromgif($file);
                    $source_image = imagecreatefromgif($file);
                    $type = '.gif';
                    break;
                default: die('image type not supported');
            }

            $fileName = $user->getRandomAuth().$type;

            move_uploaded_file($_FILES['croppedImage']['tmp_name'], $this->getParameter('photo_directory2')."/".$fileName );

            // The file
            $filename = $this->getParameter('photo_directory2')."/".$fileName;
            $size = filesize($filename);

            if($size > 4000000){
                $percent = 0.05;
            } else if ($size > 3000000 && $size < 4000000){
                $percent = 0.2;
            } else if ($size > 2000000 && $size < 3000000){
                $percent = 0.3;
            } else if ($size > 1000000 && $size < 2000000){
                $percent = 0.4;
            } else if ($size > 500000 && $size < 1000000){
                $percent = 0.5;
            } else if ($size > 250000 && $size < 500000){
                $percent = 0.6;
            } else if ($size > 100000 && $size < 250000){
                $percent = 0.8;
            } else {
                $percent = 1;
            }
            // Content type
            header("Content-Type: $fileExtention");

            // Get new dimensions
            list($width, $height) = getimagesize($filename);

            $new_width = $width * $percent;
            $new_height = $height * $percent;

            $image_p = imagecreatetruecolor($new_width, $new_height);

            $gd_image_src = null;

            // Resample
            switch( $fileExtention ){
                case 'image/png' :
                    $gd_image_src = imagecreatefrompng($filename);
                    imagealphablending( $image_p, false );
                    imagesavealpha( $image_p, true );
                    break;
                case 'image/jpeg': case 'jpg': $gd_image_src = imagecreatefromjpeg($filename); 
                    break;
                case 'image/gif' : $gd_image_src = imagecreatefromgif($filename); 
                    break;
                default: break;
            }

            imagecopyresampled($image_p, $gd_image_src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
            $newFileName = $filename;
            // Output
            switch( $fileExtention ){
                case 'image/png' : imagepng($image_p, $newFileName); break;
                case 'image/jpeg' : case 'image/jpg' : imagejpeg($image_p, $newFileName, $jpegQuality); break;
                case 'image/gif' : imagegif($image_p, $newFileName); break;
                default: break;
            }

            $data['msg'] = $percent;
        } else {
            $data['msg'] = "something wrong";
        }

        return new JsonResponse($data['msg']);

    }

    /**
     * @Route("/profile/crop/{id}", name="crop_profile_picture")
     */
    public function pictureCropAction(Request $request, $id)
    {
        
        $data = [];
        $user = $this->get('security.token_storage')->getToken()->getUser();
        $data['user'] = $user;

        $photo = $this->getDoctrine()->getManager()
            ->getRepository('AppBundle:Photo')
            ->find($id);

        $data['img'] = $photo->getFilename();
        $data['id'] = $photo->getId();

        return $this->render('photo/crop.html.twig', $data );

    }


}