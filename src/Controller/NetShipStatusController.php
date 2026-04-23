<?php

namespace App\Controller;

use App\Form\NetProjektStatusModType;
use App\Form\NetProjektStatusAddType;
use App\Entity\NetProjektStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\NetProjektStatusRepository;
use Symfony\Component\Form\FormFactoryInterface;

class NetShipStatusController extends AbstractController
{
    #[Route('/net/ship/status', name: 'ship_status')]
    public function index( Request $request, NetProjektStatusRepository $projektStatusRepository, EntityManagerInterface $em, FormFactoryInterface $formFactory): Response
    {
        $projektStatusObjecte = $projektStatusRepository->findAll();

        $bestellungKostenstellenFormArray = [];
        foreach ($projektStatusObjecte as $statusObjekt)
        {
            $formName = 'status_mod_' . $statusObjekt->getId();
            $projektStatusForm = $formFactory->createNamed($formName, NetProjektStatusModType::class, $statusObjekt);
            $projektStatusForm->handleRequest($request);

            if($projektStatusForm->isSubmitted() && $projektStatusForm->isValid())
            {
                /** @todo: Fehlerbehebung erforderlich ...... */
                //$this->addFlash('error', 'Fehler: Derzeit sind keine änderungen Möglich.');
                //return $this->redirect($this->generateUrl('ship_status'));
                try
                {
                    $em->flush();

                    $this->addFlash('success', 'Der Status wurde erfolgreich geändert.');

                    return $this->redirect($this->generateUrl('ship_status'));
                }
                catch (\Exception $exception)
                {
                    $this->addFlash('error', 'Fehler: Der Status konnte NICHT gespeichert werden. ('  . $exception->getMessage() . ').');

                    return $this->redirect($request->headers->get('referer'));
                }
            }
            $bestellungKostenstellenFormArray[] = $projektStatusForm->createView();
        }

        $projektStatusNew = new NetProjektStatus();
        $statusFormNew = $this->createForm(NetProjektStatusAddType::class, $projektStatusNew);
        $statusFormNew->handleRequest($request);
        if($statusFormNew->isSubmitted())
        {
            $em->persist($projektStatusNew);
            $em->flush();

            $this->addFlash('success', 'Der neue Status wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('ship_status'));
        }

        $breadcrumb  = '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Projekt Status</a></li>';

        return $this->render( 'net_einstellungen/shipstatus.html.twig', [
            'headline'        => 'Projekt Status',
            'breadcrumb'      => $breadcrumb,
            'statusFormArray' => $bestellungKostenstellenFormArray,
            'statusFormNew'   => $statusFormNew,
        ] );
    }

    #[Route('/api/shipstatus/liste', name: 'api_shipstatus_list')]
    public function apiSipStatus(NetProjektStatusRepository $repository)
    {
        $projektObjekte = $repository->findAll();

        $projektArray   = [];
        $projektArray[] = '';
        foreach ( $projektObjekte as $statusObjekt )
        {
            $wert  = $statusObjekt;
            $id    = $statusObjekt->getId();
            $name  = '(' . $statusObjekt->getNummer() . ') ';
            $name .= $statusObjekt->getBezeichnung();
            $projektArray[$id] = $name;
        }

        return new JsonResponse($projektArray);
    }
}
