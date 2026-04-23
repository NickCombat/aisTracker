<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class ShipAisController  extends _extensController
{
    #[Route('/admin/ais/control', name: 'ais_control')]
    public function control( Request $request ): Response
    {
        $projectDir = $this->getParameter( 'kernel.project_dir' );
        $lockFile   = $projectDir . '/var/log/ais_stream.lock';

        //$cmdCheck   = "ps aux | grep 'app:ais-listen' | grep -v grep | awk '{print $2}'";
        $cmdCheck   = "ps aux | grep '[a]pp:ais-listen' | awk '{print $2}'";
        exec($cmdCheck, $output);

        // Wenn das Array nicht leer ist, haben wir PIDs
        $pids = array_map('trim', array_filter($output));
        $isRunning = ! empty( $pids );
        $mainPid   = $isRunning ? $pids[0] : null;

        $action = $request->query->get( 'action' );

        if ( $action === 'start' )
        {
            touch( $lockFile );
            if ( ! $isRunning )
            {
                // Start im Hintergrund
                $command = sprintf( 'php %s/bin/console app:ais-listen > /dev/null 2>&1 &', $projectDir );
                exec( $command );
                sleep( 1 );
            }

            $this->addFlash( 'notice', 'AIS Stream gestartet.' );

            return $this->redirectToRoute( 'ais_control' );
        }

        if ( $action === 'stop' )
        {
            if ( file_exists( $lockFile ) )
            {
                unlink( $lockFile );
            }
            exec( "pkill -f 'app:ais-listen'" );
            $this->addFlash( 'warning', 'AIS Stream gestoppt.' );

            return $this->redirectToRoute( 'ais_control' );
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
                'label' => 'AIS Stream Monitor',
            ]
        ];

        return $this->render( 'admin/ais_status.html.twig', [
                        'breadcrumbs' => $breadcrumbs,
                        'is_running'  => $isRunning,
                        'pid'         => $mainPid,
                        'lock_exists' => file_exists( $lockFile )
        ] );
    }

}
