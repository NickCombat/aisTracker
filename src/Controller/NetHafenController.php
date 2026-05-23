<?php

namespace App\Controller;

use App\Entity\NetPort;
use App\Form\NetPortAddType;
use App\Form\NetPortModType;
use App\Repository\NetPortRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Flaggenstaaten;

class NetHafenController extends AbstractController
{
    #[Route('/net/hafen', name: 'hafen_edit')]
    public function index(Request $request, NetPortRepository $netPortRepository, EntityManagerInterface $em): Response
    {
        $portObjArray = $netPortRepository->findAll();

        $formArray = [];
        $submittedId = $request->request->get('submit_id');
        foreach ($portObjArray as $portObj)
        {
            if ($portObj->getId() != $submittedId)
            {
                $formArray[] = $this->createForm(NetPortModType::class, $portObj)->createView();
                continue;
            }

            $form = $this->createForm(NetPortModType::class, $portObj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $em->persist($portObj);
                $em->flush();

                $this->addFlash('success', 'Der Eintrag ' . $portObj->getKuerzel() . ' wurde erfolgreich gespeichert.');
                return $this->redirectToRoute('hafen_edit');
            }

            $formArray[] = $form->createView();
        }

        $flaggenMap = $em->getRepository(Flaggenstaaten::class)->findFlagByLand();
        $portNew = new NetPort();
        $formNew = $this->createForm(NetPortAddType::class, $portNew);
        $formNew->handleRequest($request);
        if($formNew->isSubmitted() && $formNew->isValid())
        {
            $em->persist($portNew);
            $em->flush();

            $this->addFlash('success', 'Der neue Hafen wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('hafen_edit'));
        }

        $breadcrumb = '<li class="breadcrumb-item"><a href="' . $this->generateUrl('net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Hafenliste</a></li>';

        return $this->render('net_hafen/index.html.twig', [
            'headline'   => 'Einstellungen Hafenliste',
            'breadcrumb' => $breadcrumb,
            'flaggenMap' => $flaggenMap,
            'formArray'  => $formArray,
            'formNew'    => $formNew->createView(),

        ]);
    }

    #[Route('/net/hafenupdate', name: 'ship_update_port_data')]
    public function hafenupdate(Request $request, NetPortRepository $netPortRepository, EntityManagerInterface $em): Response
    {
        $portObjArray = $netPortRepository->findAll();

        $formArray = [];
        $submittedId = $request->request->get('submit_id');
        foreach ($portObjArray as $portObj)
        {
            if ($portObj->getId() != $submittedId)
            {
                $formArray[] = $this->createForm(NetPortModType::class, $portObj)->createView();
                continue;
            }

            $form = $this->createForm(NetPortModType::class, $portObj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                $em->persist($portObj);
                $em->flush();

                $this->addFlash('success', 'Der Eintrag ' . $portObj->getKuerzel() . ' wurde erfolgreich gespeichert.');
                return $this->redirectToRoute('hafen_edit');
            }

            $formArray[] = $form->createView();
        }

        $flaggenMap = $em->getRepository(Flaggenstaaten::class)->findFlagByLand();
        $portNew = new NetPort();
        $formNew = $this->createForm(NetPortAddType::class, $portNew);
        $formNew->handleRequest($request);
        if($formNew->isSubmitted() && $formNew->isValid())
        {
            $em->persist($portNew);
            $em->flush();

            $this->addFlash('success', 'Der neue Hafen wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('hafen_edit'));
        }

        $breadcrumb = '<li class="breadcrumb-item"><a href="' . $this->generateUrl('net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Hafenliste</a></li>';

        return $this->render('net_hafen/index.html.twig', [
            'headline'   => 'Einstellungen Hafenliste',
            'breadcrumb' => $breadcrumb,
            'flaggenMap' => $flaggenMap,
            'formArray'  => $formArray,
            'formNew'    => $formNew->createView(),

        ]);
    }
}
