<?php

namespace App\Controller;

use App\Entity\SecUser;
use App\Form\SecUserType;
use App\Repository\SecUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Entity\LoginLog;
use App\Repository\LoginLogRepository;

#[Route('/sec/user')]
final class SecUserController extends AbstractController
{
    public function __construct(
        private readonly TranslatorInterface        $translator
    ){}

    #[Route(name: 'sec_user_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(SecUserRepository $secUserRepository): Response
    {
        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'Benutzerliste',
            ]
        ];

        return $this->render('sec_user/index.html.twig', [
            'headline'      => $this->translator->trans('Benutzerliste'),
            'breadcrumbs'   => $breadcrumbs,
            'sec_users'     => $secUserRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new SecUser();
        $form = $this->createForm(SecUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // Passwort hashen (bei NEUEN Usern Pflicht, auch wenn das Form required=false sagt, Logik hier erzwingen)
            $plainPassword = $form->get('plainPassword')->getData();
            if (empty($plainPassword))
            {
                $plainPassword = 'start123';
            }

            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashedPassword);

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('sec_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home',
                'route' => 'app_home'
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'Benutzerliste',
                'route' => 'sec_user_index'
            ],
            [
                'label' => 'breadcrumb.seitenparameter',
            ]
        ];

        return $this->render('sec_user/new.html.twig', [
            'breadcrumbs'   => $breadcrumbs,
            'sec_user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, SecUser $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(SecUserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Nur hashen, wenn der Admin ein NEUES Passwort eingetippt hat
            $plainPassword = $form->get('plainPassword')->getData();
            if (!empty($plainPassword)) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            return $this->redirectToRoute('sec_user_index', [], Response::HTTP_SEE_OTHER);
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'Benutzerliste',
                'route' => 'sec_user_index'
            ],
            [
                'label' => 'breadcrumb.seitenparameter',
            ]
        ];

        return $this->render('sec_user/edit.html.twig', [
            'breadcrumbs'   => $breadcrumbs,
            'sec_user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'admin_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, SecUser $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('sec_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/profile', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher): Response
    {
        /** @var \App\Entity\SecUser $user */
        $user = $this->getUser(); // Wir holen strikt den aktuell eingeloggten User

        // Wir nutzen das gleiche Formular wie im Admin-Bereich...
        $form = $this->createForm(SecUserType::class, $user);

        // entfernen das Feld für die Rechte (Roles), damit er sich nicht befördern kann!
        // Falls Ihr Formular-Feld anders heißt (z.B. 'roles' oder 'isAdmin'), hier anpassen.
        $form->remove('roles');
        $form->remove('email'); // Optional: Falls er seine E-Mail (Login) nicht ändern darf

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // Optional: Wenn er auch sein Passwort ändern will, müssen wir es neu hashen
            // Prüfen Sie, ob Ihr Formular ein 'plainPassword' Feld hat
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $user->setPassword(
                    $userPasswordHasher->hashPassword($user, $plainPassword)
                );
            }

            $em->flush();

            $this->addFlash('success', 'Profil erfolgreich aktualisiert.');

            return $this->redirectToRoute('app_profile');
        }

        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'Benutzer Bearbeiten',
                'route' => 'sec_user_index'
            ],
        ];

        return $this->render('sec_user/profile.html.twig', [
            'breadcrumbs'   => $breadcrumbs,
            'sec_user'      => $user,
            'form'          => $form->createView(),
        ]);
    }

    #[Route('/loginlog',  name: 'sec_user_login_log', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function loginLog(LoginLogRepository $loginLogRepository): Response
    {
        $breadcrumbs = [
            [
                'label' => 'breadcrumb.home', // Translation key
                'route' => 'app_home'         // Route name for the link
            ],
            [
                'label' => 'breadcrumb.net_einstellungen',
                'route' => 'net_einstellungen'
            ],
            [
                'label' => 'Benutzerliste',
                'route' => 'sec_user_index'
            ],
            [
                'label' => 'Login Log',
            ]
        ];
#dd($loginLogRepository->findAll());
        return $this->render('sec_user/loginlog.html.twig', [
            'headline'      => $this->translator->trans('Login Log'),
            'breadcrumbs'   => $breadcrumbs,
            'sec_users'     => $loginLogRepository->findBy([], ['loginTime' => 'DESC']),
        ]);
    }

    //#[Route('/admin/debug-login', name: 'admin_debug_login')]
    //public function debugLogin( EntityManagerInterface $em, UserPasswordHasherInterface $hasher ): Response
    //{
    //    // 1. User laden
    //    $user = $em->getRepository( \App\Entity\SecUser::class )
    //               ->findOneBy( [ 'email' => 'secBremen@millenni.info' ] );
    //
    //    if ( ! $user )
    //    {
    //        return new Response( "User nicht gefunden." );
    //    }
    //
    //    // 2. Passwort prüfen (Simuliert den echten Login-Prozess)
    //    $inputPassword = 'DCterra$!%415';
    //    $isValid = $hasher->isPasswordValid( $user, $inputPassword );
    //
    //    $html = "<h1>Login Diagnose</h1>";
    //    $html .= "User: " . $user->getUserIdentifier() . "<br>";
    //    $html .= "Klasse: " . get_class( $user ) . "<br>";
    //    $html .= "Gespeicherter Hash: " . substr( $user->getPassword(), 0, 15 ) . "...<br><br>";
    //
    //    if ( $isValid )
    //    {
    //        $html .= "<h2 style='color:green'>CHECK OK: Symfony akzeptiert das Passwort!</h2>";
    //        $html .= "Wenn der Login im Formular trotzdem nicht geht, liegt es am Browser-Cache oder CSRF-Token. Testen Sie im Inkognito-Fenster.";
    //    }
    //    else
    //    {
    //        $html .= "<h2 style='color:red'>FEHLER: Symfony akzeptiert das Passwort NICHT.</h2>";
    //        $html .= "Das bedeutet: Die Config unter 'password_hashers' passt nicht zur Entity.";
    //    }
    //
    //    return new Response( $html );
    //}

    //#[Route( '/admin/fix-secuser', name: 'admin_fix_secuser' )]
    //public function fixSecUser( EntityManagerInterface $em, UserPasswordHasherInterface $hasher ): Response
    //{
    //    $email = 'combat@millenni.info'; // Prüfen Sie, ob diese Email in der Tabelle für SecUser existiert!
    //    $rawPw = 'DCterra$!%415';
    //
    //    // 1. SecUser laden
    //    $userRepo = $em->getRepository( SecUser::class );
    //    $user = $userRepo->findOneBy( [ 'email' => $email ] );
    //
    //    if ( ! $user )
    //    {
    //        return new Response( "FEHLER: Kein SecUser mit der E-Mail '$email' gefunden. Prüfen Sie die Tabelle, auf die SecUser zugreift." );
    //    }
    //
    //    // 2. Passwort neu hashen
    //    $hash = $hasher->hashPassword( $user, $rawPw );
    //    $user->setPassword( $hash );
    //
    //    $em->persist( $user );
    //    $em->flush();
    //
    //    return new Response( "Passwort für SecUser '$email' wurde neu gesetzt. Hash: " . substr( $hash, 0, 10 )
    //                         . "... <br>Bitte jetzt einloggen." );
    //}
}
