<?php

namespace App\Controller;

use App\Entity\NetKomponentenType;
use App\Form\KomponentenTypenAddType;
use App\Form\KomponentenTypenModType;
use App\Repository\NetKomponentenTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\NetSeitenParameter;
use App\Repository\NetSeitenParameterRepository;
use App\Form\SeitenEinstellungenModType;
use App\Form\SeitenEinstellungenAddType;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER')]
class NetEinstellungenController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface        $translator
    ){}

    #[Route('/einstellungen', name: 'net_einstellungen')]
    public function index(Request $request, EntityManagerInterface $em): Response
    {

        $breadcrumb  = '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'net_einstellungen') . '">';
        $breadcrumb .= $this->translator->trans('Einstellungen');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#"></a></li>';

        return $this->render('net_einstellungen/index.html.twig', [
            'headline'                => $this->translator->trans('Einstellungen Komponententypen'),
            'breadcrumb'              => $breadcrumb,
        ]);
    }


    /**
     * @throws NotFoundExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    #[Route( '/einstellungen/seitenpatameter/liste', methods: [ 'GET', 'HEAD', 'POST' ], name: 'einstellungen_seitenpatameter_liste' )]
    #[IsGranted('ROLE_ADMIN')]
    public function einstellungenSeitenpatameter(
        NetSeitenParameterRepository $seitenParameter,
        Request                      $request,
        EntityManagerInterface       $em
    ): Response
    {
        // --- Formular zum HINZUFÜGEN vorbereiten ---
        $parameterTypenNew = new NetSeitenParameter();
        $parameterTypenFormNew = $this->createForm(SeitenEinstellungenAddType::class, $parameterTypenNew);
        $parameterTypenFormNew->handleRequest($request);

        // Diese Logik wird NUR ausgeführt, wenn das "Hinzufügen"-Formular abgeschickt wurde
        if ($parameterTypenFormNew->isSubmitted() && $parameterTypenFormNew->isValid())
        {
            $em->persist($parameterTypenNew);
            $em->flush();
            $this->addFlash('success', $this->translator->trans('Der Seiten Parameter wurde erfolgreich hinzugefügt.'));
            return $this->redirect($this->generateUrl('einstellungen_seitenpatameter_liste'));
        }

        // --- Formulare zum ÄNDERN vorbereiten ---
        $seitenParameterObjekte = $seitenParameter->findAll();
        $parameterTypenFormArr = [];
        $formFactory = $this->container->get('form.factory'); // Form Factory holen

        foreach ($seitenParameterObjekte as $parameterObjekt)
        {
            // KORREKTUR: Jedem Formular einen einzigartigen Namen geben (z.B. mit der ID)
            $formName = 'seiten_einstellungen_mod_' . $parameterObjekt->getId();

            $parameterForm = $formFactory->createNamed(
                $formName,
                SeitenEinstellungenModType::class,
                $parameterObjekt
            );

            $parameterForm->handleRequest($request);

            if ($parameterForm->isSubmitted() && $parameterForm->isValid())
            {
                // Die Logik hier wird NUR ausgeführt, wenn ein "Ändern"-Formular abgeschickt wurde
                if ($parameterForm->get('mod')->isClicked()) {
                    $em->flush(); // Speichert nur die Änderungen am $parameterObjekt
                    $this->addFlash('success', $this->translator->trans('Der Seiten Parameter wurde erfolgreich geändert.'));
                }

                if ($parameterForm->get('delete')->isClicked()) {
                    $em->remove($parameterObjekt);
                    $em->flush();
                    $this->addFlash('warning', $this->translator->trans('Der Seiten Parameter wurde gelöscht.'));
                }

                return $this->redirect($this->generateUrl('einstellungen_seitenpatameter_liste'));
            }

            $parameterTypenFormArr[] = $parameterForm->createView();
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home',
                'route' => 'app_home'
            ],
            [
                'label' => 'breadcrumb.einstellungen',
                'route' => 'einstellungen'
            ],
            [
                'label' => $this->translator->trans('Benutzer Liste'),
            ]
        ];


        return $this->render( 'einstellungen/seitenParameter.html.twig', [
            'headline'               => $this->translator->trans('Einstellungen Seiten Parameter'),
            'breadcrumbs'            => $breadcrumbs,
            'parameterTypenFormArr'  => $parameterTypenFormArr,
            'parameterTypenFormNew'  => $parameterTypenFormNew->createView(),
        ] );
    }

}
