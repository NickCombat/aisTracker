<?php

namespace App\Controller;

use App\Entity\NetPort;
use App\Form\NetReedereienAddType;
use App\Form\NetReedereienModType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Flaggenstaaten;
use App\Repository\NetEignerRepository;
use App\Entity\NetEigner;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class NetReedereienController extends AbstractController
{
    #[Route('/net/reedereien', name: 'reedereien_edit')]
    public function index( Request $request, NetEignerRepository $reedereitRepository, EntityManagerInterface $em): Response
    {
        $portObjArray = $reedereitRepository->findAll();

        $formArray    = [];
        $submittedId  = $request->request->get('submit_id');
        foreach ($portObjArray as $portObj)
        {
            if ($portObj->getId() != $submittedId)
            {
                $formArray[] = $this->createForm(NetReedereienModType::class, $portObj)->createView();
                continue;
            }

            $form = $this->createForm(NetReedereienModType::class, $portObj);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid())
            {
                /** @var UploadedFile $wappenFile */
                $wappenFile = $form->get('wappen')->getData();

                // Wenn eine neue Datei hochgeladen wurde
                if ($wappenFile)
                {
                    $newFilename = uniqid().'.'.$wappenFile->guessExtension();

                    try
                    {
                        // Verschiebe die Datei in den Zielordner
                        $wappenFile->move(
                            $this->getParameter('kernel.project_dir') . '/public/img/wappen',
                            $newFilename
                        );

                        // Alten Dateinamen optional vom Server löschen, falls gewünscht
                        if ($portObj->getWappen() && file_exists($this->getParameter('kernel.project_dir') . '/public/img/wappen/' . $portObj->getWappen()))
                        {
                            unlink($this->getParameter('kernel.project_dir') . '/public/img/wappen/' . $portObj->getWappen());
                        }

                        // Neuen Dateinamen in der Entität setzen
                        $portObj->setWappen($newFilename);
                    }
                    catch (FileException $e)
                    {
                        $this->addFlash('error', 'Fehler beim Upload des Wappens.');
                    }
                }

                $em->persist($portObj);
                $em->flush();

                $this->addFlash('success', 'Der Eintrag ' . $portObj->getKuerzel() . ' wurde erfolgreich gespeichert.');
                return $this->redirectToRoute('reedereien_edit');
            }

            $formArray[] = $form->createView();
        }

        // --- Logik für $formNew (Neue Reederei) ---
        $portNew = new NetEigner();
        $formNew = $this->createForm(NetReedereienAddType::class, $portNew);
        $formNew->handleRequest($request);

        if($formNew->isSubmitted() && $formNew->isValid())
        {
            /** @var UploadedFile $wappenFileNew */
            $wappenFileNew = $formNew->get('wappen')->getData();

            if ($wappenFileNew)
            {
                $newFilename = uniqid().'.'.$wappenFileNew->guessExtension();
                try
                {
                    $wappenFileNew->move(
                        $this->getParameter('kernel.project_dir') . '/public/img/wappen',
                        $newFilename
                    );
                    $portNew->setWappen($newFilename);
                }
                catch (FileException $e)
                {
                    // Fehlerbehandlung
                }
            }

            $em->persist($portNew);
            $em->flush();

            $this->addFlash('success', 'Die neue Reederrei ' . $portNew->getBezeichnung() . ' wurde erfolgreich hinzugefügt.');

            return $this->redirect($this->generateUrl('reedereien_edit'));
        }

        $breadcrumb = '<li class="breadcrumb-item"><a href="' . $this->generateUrl('net_einstellungen') . '">Einstellungen</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Reederreien</a></li>';

        return $this->render('net_reedereien/index.html.twig', [
            'headline'   => 'Einstellungen Reedereien',
            'breadcrumb' => $breadcrumb,
            //'flaggenMap' => $flaggenMap,
            'formArray'  => $formArray,
            'formNew'    => $formNew->createView(),

        ]);
    }
}
