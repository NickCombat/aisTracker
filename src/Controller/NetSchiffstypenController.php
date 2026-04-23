<?php

namespace App\Controller;

use App\Entity\NetShipTyp;
use App\Form\NetShipTypAddType;
use App\Form\NetShipTypModType;
use App\Repository\NetShipTypRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NetSchiffstypenController extends AbstractController
{
    #[Route('/einstellungen/schiffstypen/liste', name: 'schiffstypen_edit')]
    public function index(Request $request, NetShipTypRepository $shipTypRepository , EntityManagerInterface $em): Response
    {
        $portObjArray = $shipTypRepository->findAll();

        $formArray = [];
        $submittedId = $request->request->get('submit_id');
        foreach ($portObjArray as $portObj)
        {
            if ($portObj->getId() != $submittedId)
            {
                $formArray[] = $this->createForm(NetShipTypModType::class, $portObj)->createView();
                continue;
            }

            $form = $this->createForm(NetShipTypModType::class, $portObj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $em->persist($portObj);
                $em->flush();

                $this->addFlash('success', 'Der Eintrag "' . $portObj->getBezeichnung() . '" wurde erfolgreich gespeichert.');

                return $this->redirectToRoute('schiffstypen_edit');
            }

            $formArray[] = $form->createView();
        }

        $portNew = new NetShipTyp();
        $formNew = $this->createForm(NetShipTypAddType::class, $portNew);
        $formNew->handleRequest($request);
        if($formNew->isSubmitted() && $formNew->isValid())
        {
            $em->persist($portNew);
            $em->flush();

            $this->addFlash('success', 'Der neue Schiffstyp wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('schiffstypen_edit'));
        }



        $headline  = 'Einstellungen Schiffstypen';

        $breadcrumb = '<li class="breadcrumb-item"><a href="' . $this->generateUrl('net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Schiffstypen</a></li>';

        return $this->render('net_schiffstypen/index.html.twig', [
            'headline'   => $headline,
            'breadcrumb' => $breadcrumb,
            'formArray'  => $formArray,
            'formNew'    => $formNew->createView(),
        ]);
    }
}
