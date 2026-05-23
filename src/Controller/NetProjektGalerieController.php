<?php

namespace App\Controller;

use App\Entity\NetKomponenten;
use App\Entity\NetProjektGalerie;
use App\Entity\NetShipdata;
use App\Form\ImageUploadType;
use App\Form\NetArtikelGalerieAddType;
use App\Form\NetProjektGalerieAddType;
use App\Repository\NetProjektGalerieRepository;
use App\Repository\NetShipdataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use App\Service\GalerieService;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route( '/net/galerie' )]
class NetProjektGalerieController  extends GalerieService
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/{id}', name: 'projekt_galerie')]
    public function index($id, NetProjektGalerieRepository $galerieRepository, NetShipdataRepository $shipdataRepository): Response
    {
        $shipdataObject = $shipdataRepository->find($id);

        $galerieObj = $galerieRepository->findByProjekt($id);

        $headline = '' . $shipdataObject->getName();
        if ($shipdataObject->getImo() != '0')
        {
            $headline .= ' - ' . $shipdataObject->getImo();
        }

                $breadcrumb  = '<li class="breadcrumb-item"><a href="/">';
        $breadcrumb .= $this->translator->trans('Home');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">Projekte Übersicht</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekt_deails', [ 'id' => $shipdataObject->getId() ]) . '">Projekt ' . $shipdataObject->getName() . '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="#">Projekt Galerie</a></li>';

        return $this->render('net_projekt_galerie/index.html.twig', [
            'headline'    => $headline,
            'breadcrumb'  => $breadcrumb,
            'galerie'      => $galerieObj,
            'shipdata'    => $shipdataObject,
            'projekt_id'  => $shipdataObject->getId(),
            'anlagenArray'=> $shipdataObject->getNetProjektAnlagens(),
        ]);
    }

    #[Route('/komponente/{id}/image-upload', methods: ['POST'], name: 'komponente_image_upload')]
    public function komponenteImageAdd( NetKomponenten $komponente, Request $request,EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ImageUploadType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() )//&& $form->isValid())
        {
            /** @var UploadedFile[] $files */
            $files    = $form->get('images')->getData();
            $comment  = $form->get('comment')->getData();
            $filename = $form->get('filename')->getData();
            $shipdataObject = $komponente->getShipdata();

            foreach ($files as $file)
            {
                $filesize     = $file->getSize();
                $filetype     = $file->getMimeType();
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = uniqid() . '-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($originalFilename));
                $newFilename  = $safeFilename . '.' . $file->guessExtension();

                try
                {
                    $this->kompressImg($file, $newFilename);
                }
                catch (FileException $e)
                {
                    $this->addFlash('error', 'Fehler beim Hochladen der Datei. (' . $e->getMessage() . ')');
                    continue;
                }
                if(!$filename)
                {
                    $filename = $originalFilename;
                }

                $bild = new NetProjektGalerie();
                $bild->setFilename($filename)
                     ->setBasename($newFilename)
                     ->setOriginalName($originalFilename)
                     ->setFilesize($filesize)
                     ->setFiletype($filetype)
                     ->setProjekt($shipdataObject)
                     ->setKomponente($komponente)
                     ->setBermerkung($comment);
                $em->persist($bild);
            }

            $em->flush();
            $this->addFlash('success', 'Bilder erfolgreich hochgeladen.');
        }

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/artikel/image/add', methods: ['POST'], name: 'artikel_image_add')]
    public function artikelImageAdd(Request $request, EntityManagerInterface $em )
    {
        $form = $this->createForm(NetArtikelGalerieAddType::class);

        $form->handleRequest($request);

        if($form->isSubmitted())
        {

            $shipdataId   = null;
            $komponenteId = null;
            if ($form->has('komponenteId') && $form->get('komponenteId')->getData() !== null)
            {
                $komponenteId = $form->get('komponenteId')->getData();
            }
            if ($form->has('shipdataId') && $form->get('shipdataId')->getData() !== null)
            {
                $shipdataId = $form->get('shipdataId')->getData();
            }
            $filename = $form->get('filename')->getData();
            $anlage = $form->get('anlage')->getData();

            if($komponenteId)
            {
                $komponente     = $em->getRepository(NetKomponenten::class)->find($komponenteId);
                $shipdataObject = $komponente->getShipdata();
            }
            if($shipdataId)
            {
                $shipdataObject = $em->getRepository(NetShipdata::class)->find($shipdataId );
            }

            $galerieImmage = new NetProjektGalerie();
            if ($anlage)
            {
                if(!$filename)
                {
                    $filename = $anlage->getClientOriginalName();
                }
                $dateiname    = md5(uniqid()) . '.' . $anlage->guessClientExtension();
                $originalName = $anlage->getClientOriginalName();
                $filesize     = $anlage->getSize();
                $filetype     = $anlage->getMimeType();
                $this->kompressImg($anlage, $dateiname);

                $galerieImmage->setFilename($filename)
                    ->setBasename($dateiname)
                    ->setOriginalName($originalName)
                    ->setFilesize($filesize)
                    ->setFiletype($filetype)
                    ->setProjekt($shipdataObject);

                if(null !== $komponenteId)
                {
                    $galerieImmage->setKomponente($komponente);
                }
            }

            $em->persist($galerieImmage);
            $em->flush();

            $this->addFlash('success', 'Das Bild wurde zum Projekt hinzugefügt.');

            return $this->redirect($request->headers->get('referer'));
        }

        $this->addFlash('error', 'FEHLER: Beim speichern ist ein Fehler aufgetreten.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/artikel/image/del/{id}', methods: ['DELETE', 'GET'], name: 'artikel_image_del')]
    public function artikelImageDel(int $id, Request $request, EntityManagerInterface $em ): JsonResponse
    {
        $imageObj = $em->getRepository(NetProjektGalerie::class)->find($id);

        if (!$imageObj)
        {
            return new JsonResponse(['success' => false, 'error' => 'Bild nicht gefunden.'], 404);
        }

        try
        {
            // 1. Physische Datei löschen
            $filePath = $this->getParameter('kernel.project_dir') . '/public/img/galerie/' . $imageObj->getBaseName();
            if (file_exists($filePath))
            {
                unlink($filePath);
            }

            // 2. Datenbank-Eintrag entfernen
            $em->remove($imageObj);
            $em->flush();
        }
        catch (\Exception $e)
        {
            return new JsonResponse(['success' => false, 'error' => $e->getMessage()], 404);
        }

        return new JsonResponse(['success' => true], 200);
    }

    #[Route('/projekt/image/add', methods: ['POST'], name: 'projekt_image_add')]
    public function projektImageAdd(Request $request, EntityManagerInterface $em )
    {
        $form = $this->createForm(NetProjektGalerieAddType::class);

        $form->handleRequest($request);

        if($form->isSubmitted())
        {
            $shipdataId   = null;
            $komponenteId = null;
            if ($form->has('komponenteId') && $form->get('komponenteId')->getData() !== null)
            {
                $komponenteId = $form->get('komponenteId')->getData();
            }
            if ($form->has('shipdataId') && $form->get('shipdataId')->getData() !== null)
            {
                $shipdataId = $form->get('shipdataId')->getData();
            }
            $filename = $form->get('filename')->getData();
            $anlage = $form->get('anlage')->getData();

            if($komponenteId)
            {
                $komponente     = $em->getRepository(NetKomponenten::class)->find($komponenteId);
                $shipdataObject = $komponente->getShipdata();
            }
            if($shipdataId)
            {
                $shipdataObject = $em->getRepository(NetShipdata::class)->find($shipdataId );
            }

            $galerieImmage = new NetProjektGalerie();
            if ($anlage)
            {
                if(!$filename)
                {
                    $filename = $anlage->getClientOriginalName();
                }
                $dateiname    = md5(uniqid()) . '.' . $anlage->guessClientExtension();
                $originalName = $anlage->getClientOriginalName();
                $filesize     = $anlage->getSize();
                $filetype     = $anlage->getMimeType();

                $this->kompressImg($anlage, $dateiname);

                $galerieImmage->setFilename($filename)
                    ->setBasename($dateiname)
                    ->setOriginalName($originalName)
                    ->setFilesize($filesize)
                    ->setFiletype($filetype)
                    ->setProjekt($shipdataObject);

                if(null !== $komponenteId)
                {
                    $galerieImmage->setKomponente($komponente);
                }
            }

            $em->persist($galerieImmage);
            $em->flush();

            $this->addFlash('success', 'Das Bild wurde zum Projekt hinzugefügt.');

            return $this->redirect($request->headers->get('referer'));
        }

        $this->addFlash('error', 'FEHLER: Beim speichern ist ein Fehler aufgetreten.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/projekt/image/mod', methods: ['POST'], name: 'projekt_image_mod')]
    public function projektImageMod(Request $request, EntityManagerInterface $em )
    {
        $imageObj = $em->getRepository( NetProjektGalerie::class )
                       ->find( $request->get( 'imageId' ) );

        $change = 0;
        $message = 'Keine Änderrungen gefunden.';
        if ( $imageObj->getBermerkung() !== $request->get( 'comment' ) )
        {
            $change++;
            $imageObj->setBermerkung( $request->get( 'comment' ) );
        }
        if ( $imageObj->getFilename() !== $request->get( 'title' ) )
        {
            $change++;
            $imageObj->setFilename( $request->get( 'title' ) );
        }
        if ( $change !== 0 )
        {
            $em->persist( $imageObj );
            $em->flush();

            $message = 'Das Bild wurde wie angegeben geändert.';
        }

        $this->addFlash( 'success', $message );

        return $this->redirect($request->headers->get('referer'));
    }

    private function kompressImg($newImage, $dateiname):void
    {
        $destination = $this->getParameter('galerieProjektOrdner');
        // Pfad zur temporären Datei (direkt nach dem Upload)
        $tempPath  = $newImage->getRealPath();
        $finalPath = $destination . '/' . $dateiname;

        // Bild Kompression aufrufen
        $this->compressAndScaleImage($tempPath, $finalPath);
    }
}