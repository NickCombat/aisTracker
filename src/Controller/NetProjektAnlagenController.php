<?php

namespace App\Controller;

use App\Entity\NetProjektAnlagen;
use App\Form\NetProjektAnlagenAddType;
use App\Repository\NetShipdataRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\GalerieService;
use Symfony\Contracts\Translation\TranslatorInterface;

class NetProjektAnlagenController extends GalerieService
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    #[Route('/net/projekt/anlagen', name: 'projekt_anlagen')]
    public function index(): Response
    {
        return $this->render('net_projekt_anlagen/index.html.twig', [
            'controller_name' => 'NetProjektAnlagenController',
        ]);
    }

    #[Route('/projekt/anlagen/add/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_anlagen_add')]
    public function anlagenAdd(int $id, NetShipdataRepository $shipdataReposetory, Request $request, EntityManagerInterface $em): Response
    {
        $shipdataObject = $shipdataReposetory->find($id);

        $headline = '' . $shipdataObject->getName();
        if ($shipdataObject->getImo() != '0')
        {
            $headline .= ' - ' . $shipdataObject->getImo();
        }
        $projektAnlagenArray = $shipdataObject->getNetProjektAnlagens();

        $form = $this->createForm(NetProjektAnlagenAddType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            /** @var UploadedFile $file */
            $file = $form->get('anlage')->getData();
            if ($file)
            {
                $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugify($originalName);
                $newFilename  = $safeFilename.'-'.md5(uniqid()).'.'.$file->guessExtension();

                $projektAnlagenObject = new NetProjektAnlagen();
                if ($request->files->get('net_projekt_anlagen_add'))
                {
                    $payload  = $request->getPayload()->all();
                    $filename = '';
                    if(isset($payload['net_projekt_anlagen_add']['filename']))
                    {
                        $filename = $payload['net_projekt_anlagen_add']['filename'];
                    }

                    if (empty($file))
                    {
                        $message = $this->translator->trans('FEHLER: Es wurde keine Datei ausgewählt.');
                        $this->addFlash('warning', $message);

                        return $this->redirect($request->headers->get('referer'));
                    }
                    $revision     = $payload['net_projekt_anlagen_add']['revision'];

                    if(!$filename)
                    {
                        $filename = $file->getClientOriginalName();
                    }
                    $originalName = $file->getClientOriginalName();
                    $filesize     = $file->getSize();
                    $filetype     = $file->getMimeType();

                    try
                    {
                        if('pdf' === $file->guessExtension())
                        {
                            $file->move($this->getParameter('anlagenProjektOrdner'), $newFilename);
                        }
                        else
                        {
                            $destination = $this->getParameter( 'anlagenProjektOrdner' );
                            // Pfad zur temporären Datei (direkt nach dem Upload)
                            $tempPath = $file->getRealPath();
                            $finalPath = $destination . '/' . $newFilename;

                            // Bild Kompression aufrufen
                            $this->compressAndScaleImage( $tempPath, $finalPath );
                        }
                    }
                    catch (FileException $e)
                    {
                        $message = $this->translator->trans('FEHLER: Hinzufügen zum Projekt fehlgeschlagen.');
                        $this->addFlash('warning', $message . ' (' . $e->getMessage() . ')');

                        return $this->redirect($request->headers->get('referer'));
                    }

                    $projektAnlagenObject->setFilename($filename)
                        ->setBasename($newFilename)
                        ->setOriginalName($originalName)
                        ->setFilesize($filesize)
                        ->setFiletype($filetype)
                        ->setRevision($revision)
                        ->setProjekt($shipdataObject);

                    try
                    {
                        $em->persist($projektAnlagenObject);
                        $em->flush();
                    }
                    catch (\Exception $e)
                    {
                        $message = $this->translator->trans('FEHLER: Hinzufügen zum Projekt fehlgeschlagen.');
                        $this->addFlash('warning', $message . ' (' . $e->getMessage() . ')');

                        return $this->redirect($request->headers->get('referer'));
                    }

                    $message = $this->translator->trans('Die Anlage wurde zum Projekt hinzugefügt.');
                    $this->addFlash('success', $message );
                }
            }
        }

        $breadcrumb  = '<li class="breadcrumb-item"><a href="/">';
        $breadcrumb .= $this->translator->trans('Home');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekte_uebersicht_list') . '">';
        $breadcrumb .= $this->translator->trans('Projekte Übersicht');
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekt_deails', [ 'id' => $shipdataObject->getId() ]) . '">';
        $breadcrumb .= $this->translator->trans('Projekt') . ' ' . $shipdataObject->getName();
        $breadcrumb .= '</a></li>';
        $breadcrumb .= '<li class="breadcrumb-item"><a href="' .$this->generateUrl( 'projekt_anlagen_add', [ 'id' => $shipdataObject->getId() ]) . '">';
        $breadcrumb .= $this->translator->trans('Anlagen');
        $breadcrumb .= '</a></li>';

        return $this->render('net_projekt_anlagen/index.html.twig', [
            'headline'       => $headline,
            'breadcrumb'     => $breadcrumb,
            'projekt_id'     => $shipdataObject->getId(),
            'shipdata'       => $shipdataObject,
            'form'           => $form->createView(),
            'anlagenArray'   => $projektAnlagenArray
        ]);
    }

    #[Route('/projekt/anlagen/del/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_anlagen_del')]
    public function anlagenDel(int $id, NetProjektAnlagen $projektAnlagenObject, Request $request, EntityManagerInterface $em): Response
    {
        $projektId = $projektAnlagenObject->getProjekt()->getId();

        if(!$projektAnlagenObject instanceof NetProjektAnlagen){

            $this->addFlash('error', 'Fehler. Die Anlage konnte NICHT entfernt werden.');

            return $this->redirect($this->generateUrl('projekt_anlagen_add',['id' => $projektId]));
        }
        $em->remove($projektAnlagenObject);
        $em->flush();

        $this->addFlash('success', 'Die Anlage wurde erfolgreich aus dem Projekt entfernt.');

        return $this->redirect($request->headers->get('referer'));
    }

    #[Route('/projekt/anlagen/show/{id}', methods: ['GET', 'HEAD', 'POST'], name: 'projekt_anlagen_show')]
    public function anlagenShow(int $id, NetProjektAnlagen $projektAnlagenObject, Request $request, EntityManagerInterface $em): Response
    {
        $filepath = $this->getParameter('anlagenProjektOrdner') . '/' . $projektAnlagenObject->getBasename();

        return $this->file($filepath, $projektAnlagenObject->getOriginalName());
    }

    #[Route('/projekt/anlagen/preview/{id}', name: 'projekt_anlagen_preview')]
    public function anlagenPreview(int $id, NetProjektAnlagen $projektAnlagenObject): Response
    {
        if (!$projektAnlagenObject) {
            throw $this->createNotFoundException('Datei nicht gefunden');
        }

        $filepath = $this->getParameter('anlagenProjektOrdner') . '/' . $projektAnlagenObject->getBasename();

        return new Response(file_get_contents($filepath), 200, [
            'Content-Type' => $projektAnlagenObject->getFiletype(),
            'Content-Disposition' => 'inline; filename="' . $projektAnlagenObject->getFilename() . '"'
        ]);
    }

    function slugify(string $text, int $maxLength = 100, string $separator = '-'): string
    {
        // 1. Umlaute und Sonderzeichen ersetzen
        $text = str_replace(
            ['ä', 'ö', 'ü', 'Ä', 'Ö', 'Ü', 'ß'],
            ['ae', 'oe', 'ue', 'Ae', 'Oe', 'Ue', 'ss'],
            $text
        );

        // 2. Nicht-Buchstaben/Zahlen durch Trennzeichen ersetzen
        $text = preg_replace('~[^\\pL\d]+~u', $separator, $text);

        // 3. Trimmen (Anfang/Ende)
        $text = trim($text, $separator);

        // 4. Nur erlaubte Zeichen behalten (Buchstaben, Zahlen, Trennzeichen)
        $text = preg_replace('~[^-\w]+~', '', $text);

        // 5. Kleinbuchstaben
        $text = strtolower($text);

        // 6. Maximale Länge
        $text = mb_substr($text, 0, $maxLength);

        // 7. Fallback
        return $text ?: 'n-a';
    }
}
