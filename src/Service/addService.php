<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Exception\SessionNotFoundException;

class addService
{
    /**
     * Speichert die rohen API-Antwortdaten im Dateisystem.
     *
     * @param string $rawData Die rohe String-Antwort von der API.
     * @param string $suffix
     */
    protected function saveRawData( string $rawData, string $suffix ):void
    {
        try
        {
            // Sicherstellen, dass das Verzeichnis existiert
            if (!is_dir($this->rawLogPath))
            {
                mkdir($this->rawLogPath, 0755, true);
            }
            // Eindeutigen Dateinamen erstellen
            $timestamp = (new \DateTime())->format('Y-m-d_H-i-s');
            $safeContext = preg_replace('/[^a-zA-Z0-9_-]/', '_', $suffix);
            $filename = $timestamp . '_' . $safeContext . '.json'; // Annahme, dass es JSON ist

            // Daten speichern
            file_put_contents($this->rawLogPath . $filename, $rawData);

            $message = 'AIS-Rohdaten wurden als [' . $filename . '] gespeichert.';
            $this->logOrFlash( 'debug', $message );

        }
        catch (\Exception $e)
        {
            // Wenn das Speichern fehlschlägt, soll die Hauptanfrage nicht fehlschlagen. Fehler nur loggen.
            $message = 'Fehler beim Speichern der AIS-Rohdaten: ' . $e->getMessage();
            $this->logOrFlash( 'error', $message );
        }
    }


    /**
     * Loggt eine Nachricht ODER fügt sie als Flash-Message hinzu,
     * je nach Ausführungskontext (Web oder CLI).
     */
    protected function logOrFlash( string $level, string $message ): void
    {
        $this->logger->{$level}( $message );

        try
        {
            if ( $this->requestStack->getCurrentRequest() && $this->requestStack->getSession() )            {
                $session = $this->requestStack->getSession();
                $session->getFlashBag()
                        ->add( $level, $message );
            }
        }
        catch ( SessionNotFoundException $e )
        {}
    }

}