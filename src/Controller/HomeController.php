<?php

namespace App\Controller;

use App\Repository\NetShipdataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \Symfony\Component\HttpFoundation\Request;
use App\Entity\NetShipdataPort;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(?NetShipdataRepository $shipdataRepository,Request $request, PaginatorInterface $paginator, EntityManagerInterface $em): Response
    {
        $shipdataObject = '';
        try
        {
            //$shipdataObject = $shipdataRepository->findShipdataByStatusNullOrOneWithStats();
            $queryBuilder = $shipdataRepository->createQueryBuilder( 's' )
                                               ->where( 's.status != :inaktivStatus' )
                                               ->setParameter( 'inaktivStatus', 2 )
                                               ->orderBy('s.orderno', 'ASC');

            $shipdataObject = $paginator->paginate(
                $queryBuilder,
                $request->query->getInt('page', 1), 16 );
        }
        catch (\Exception $e)
        {
            $this->addFlash('error', $e->getMessage());
        }
        //$breadcrumb  = '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">';
        //$breadcrumb .= $this->translate->trans('Projekte Übersicht' );
        //$breadcrumb .= '</a></li>';
        $hafenListe = $em->getRepository(NetShipdataPort::class)->findNextPortPerShip();

        return $this->render('home/index.html.twig', [
            'headline'   => 'Projekte Übersicht',
            'hafenListe' => $hafenListe,
            'shipdatas'  => $shipdataObject,
        ]);
    }

    #[Route('/change/local/{newLocal}', name: 'change_local')]
    public function changeLocal($newLocal, Request $request): Response
    {
        $request->getSession()->set('_locale', $newLocal);
        if(!$request->headers->get('referer'))
        {
            return $this->redirectToRoute('app_home');
        }

        return $this->redirect($request->headers->get('referer'));
    }
}
