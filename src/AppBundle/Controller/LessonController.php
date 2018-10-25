<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Lesson;
use AppBundle\Form\LessonType;

class LessonController extends Controller
{
    /**
     * @Route("lesson/home/{incr_date}/{page}", defaults={"page" = 1, "incr_date" = ""}, name="lesson_homepage")
     */
    public function indexAction(Request $request, $incr_date = "", $page = 1 )
    {
        $data = [];

        $limit = 5;
        $offset = $page * $limit - $limit;

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

        if($user != 'anon.'){
            $data['user'] = $user;

            $lesson = new Lesson();
            if($incr_date == ""){
            	$today = date("Y-m-d");
            	$data['todays_date'] = $today;
            	$lesson->setLessonDate(new \DateTime($today));
            } else {
				$datetime = new \DateTime($incr_date);
				$datetime->modify('+1 day'); 
				$nextDate = $datetime->format("Y-m-d");
            	$lesson->setLessonDate($datetime);
            	$data['todays_date'] = $incr_date;
            	$data['next_date'] = $nextDate;
            }
            

            $form = $this->createForm(LessonType::class, $lesson);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
            	
                $em->persist($lesson);
                $em->flush();

                $nextDate = $lesson->getLessonDate()->format("Y-m-d");

                $data['todays_date'] = $nextDate;

                $this->addFlash(
                    'success',
                    'Thank you for posting the lesson!'
                );

                return $this->redirectToRoute('lesson_homepage', ['incr_date' => $data['todays_date']]);
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }

        $day = date('w');
		$week_start = date("Y-m-d", strtotime('-'.(1+$day).' days'));
		$week_end = date("Y-m-d", strtotime('+'.(5-$day).' days'));

        $lessons = $em->getRepository('AppBundle:Lesson')
            ->findThisWeeksLesson($week_start, $week_end);

        if($lessons){
            $data['nextPage'] = $page + 1;
        } else {
            $data['nextPage'] = "blank";
        }

        $data['lessons'] = $lessons;



        return $this->render('lesson/home.html.twig', $data);
    }

    /**
     * @Route("/lesson/create", name="create_lesson")
     */
    public function createAction(Request $request, $page = 1 )
    {
        $data = [];

        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();

            $data['user'] = $user;

            $lesson = new Lesson();
            if($incr_date == ""){
            	$today = date("Y-m-d");
            	$data['todays_date'] = $today;
            	$lesson->setLessonDate(new \DateTime($today));
            } else {
				$datetime = new \DateTime($incr_date);
				$datetime->modify('+1 day'); 
				$nextDate = $datetime->format("Y-m-d");
            	$lesson->setLessonDate($datetime);
            	$data['todays_date'] = $incr_date;
            	$data['next_date'] = $nextDate;
            }
            
            $form = $this->createForm(LessonType::class, $lesson);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($lesson);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                return $this->redirectToRoute('lesson_homepage');
                
            }

            $data['form'] = $form->createView();


        return $this->render('lesson/create.html.twig', $data);
    }

	/**
	 *@Route("/lesson/view/{id}/{incr_date}/{page}", defaults={"page" = 1, "incr_date" = ""}, name="show_lesson")
	 */
	public function showLessonAction(Request $request, $id, $incr_date = "", $title = null )
	{
		$data = [];
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        if($user != 'anon.'){
            $data['user'] = $user;

            $lesson = new Lesson();
            if($incr_date == ""){
            	$today = date("Y-m-d");
            	$data['todays_date'] = $today;
            	$lesson->setLessonDate(new \DateTime($today));
            } else {
				$datetime = new \DateTime($incr_date);
				$datetime->modify('+1 day'); 
				$nextDate = $datetime->format("Y-m-d");
            	$lesson->setLessonDate($datetime);
            	$data['todays_date'] = $incr_date;
            	$data['next_date'] = $nextDate;
            }
            
            $form = $this->createForm(LessonType::class, $lesson);

            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $em->persist($lesson);
                $em->flush();

                $this->addFlash(
                    'success',
                    'Thank you for sharing!'
                );

                // ... do any other work - like sending them an email, etc
                // maybe set a "flash" success message for the user

                return $this->redirectToRoute('lesson_homepage');
                
            }

            $data['form'] = $form->createView();

        } else {

            $data['user'] = ['firstName'=>'Login', 'action'=>'register'];
        
        }
		$lesson = $this->getDoctrine()->getManager()
			->getRepository('AppBundle:Lesson')
			->find($id);

	    if (!$lesson) {
	        throw $this->createNotFoundException(
	            'No lesson found for that title'
	        );
	    }
                        
		$data['lesson'] = $lesson;

        return $this->render('lesson/lesson.html.twig', $data);
	}

	/**
	 *@Route("/lesson/delete/{id}", name="delete_lesson")
	 */
	public function deleteLessonAction(Request $request, $id )
	{
    	$em = $this->getDoctrine()->getManager();

		$lesson = $em->getRepository('AppBundle:Lesson')
			->find($id);

		$em->remove($lesson);
		$em->flush();	

        return $this->redirectToRoute('lesson_homepage');
  
	}

	/**
	 *@Route("/lesson/edit/{id}", name="edit_lesson")
	 */
	public function editLessonAction(Request $request, $id )
	{
		$data = [];

    	$em = $this->getDoctrine()->getManager();
        $user = $this->get('security.token_storage')->getToken()->getUser();

        $data['user'] = $user;

		$today = date("Y-m-d");
		$data['todays_date'] = $today;
		



		$lesson = $em->getRepository('AppBundle:Lesson')
			->find($id);

	    if (!$lesson) {
	        throw $this->createNotFoundException(
	            'No lesson found for that title'
	        );
	    }
        $form = $this->createForm(LessonType::class, $lesson);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($lesson);
            $em->flush();

            $this->addFlash(
                'success',
                'Edit successful!'
            );

            return $this->redirectToRoute('show_lesson', ['id' => $id]);
            
        } else {
        	$form_data['title'] = $lesson->getTitle();
        	$form_data['body'] = $lesson->getBody();
        }

        $data['form_data'] = $form_data;
        $data['form'] = $form->createView();

		$data['lesson'] = $lesson;

        return $this->render('lesson/edit.html.twig', $data);
	}


}