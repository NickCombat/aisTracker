<?php
// NetFlaggenstattenController
namespace App\Controller;

use App\Entity\Flaggenstaaten;
use App\Form\FlaggenstaatenAddType;
use App\Form\FlaggenstaatenModType;
use App\Repository\FlaggenstaatenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Form\FlagUploadType;

class NetFlaggenstattenController extends AbstractController
{
    #[Route('/net/flaggenstatten', name: 'flaggen_edit')]
    public function index(Request $request, FlaggenstaatenRepository $flaggenstaatenRepository, EntityManagerInterface $em): Response
    {
        $portObjArray = $flaggenstaatenRepository->findAll();

        $formArray = [];
        $submittedId = $request->request->get('submit_id');
        foreach ($portObjArray as $portObj)
        {
            if ($portObj->getId() != $submittedId)
            {
                $formArray[] = $this->createForm(FlaggenstaatenModType::class, $portObj)->createView();
                continue;
            }

            $form = $this->createForm(FlaggenstaatenModType::class, $portObj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $em->persist($portObj);
                $em->flush();

                $this->addFlash('success', 'Der Eintrag ' . $portObj->getAmtlicheVollform() . ' wurde erfolgreich gespeichert.');

                return $this->redirectToRoute('flaggen_edit');
            }

            $formArray[] = $form->createView();
        }

        $portNew = new Flaggenstaaten();
        $formNew = $this->createForm(FlaggenstaatenAddType::class, $portNew);
        $formNew->handleRequest($request);
        if($formNew->isSubmitted() && $formNew->isValid())
        {
            $em->persist($portNew);
            $em->flush();

            $this->addFlash('success', 'Der neue Hafen wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('flaggen_edit'));
        }
        $editForm = $this->createForm(FlagUploadType::class);

        $breadcrumb  = '<li class="breadcrumb-item"><a href="' . $this->generateUrl('net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Flaggenstaaten</a></li>';

        return $this->render('net_flaggenstatten/index.html.twig', [
            'headline'   => 'Einstellungen Flaggenstaaten',
            'breadcrumb' => $breadcrumb,
            'formArray'  => $formArray,
            'formNew'    => $formNew->createView(),
            'editForm'   => $editForm->createView(),
        ]);
    }

    #[Route('/net/flaggenstatten/upload', name: 'upload_flag', methods: ['POST'])]
    public function uploadFlag(Request $request, FlaggenstaatenRepository $repo, EntityManagerInterface $em): Response
    {
        $id = $request->request->get('flaggenstaat_id');
        $flaggenstaat = $repo->find($id);

        $form = $this->createForm(FlagUploadType::class);
        $form->handleRequest($request);

        //if ($form->isSubmitted() && $form->isValid() && $flaggenstaat) {
        if ($form->isSubmitted() && $flaggenstaat)
        {
            try
            {
                $file = $form->get( 'flag' )
                             ->getData();
                if ( $file )
                {
                    $filename = uniqid() . '.' . $file->guessExtension();
                    $file->move( $this->getParameter( 'picFlaggenstaaten' ), $filename );
                    $flaggenstaat->setFlagge( $filename );
                    $em->flush();

                    $this->addFlash( 'success', 'Flagge erfolgreich hochgeladen.' );
                }
            }
            catch (\Exception $e)
            {
                dd($e, $request);
            }
        }

        return $this->redirectToRoute('flaggen_edit');
    }
}