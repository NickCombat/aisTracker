<?php

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GalerieService extends AbstractController
{

    protected function compressAndScaleImage( string $sourcePath, string $targetPath ): void
    {
        $maxWidth = 2000;
        $quality  = 90;

        // 1. Bild-Infos holen
        if (!is_file($sourcePath))
        {
            return;
        }
        list( $width, $height, $type ) = getimagesize( $sourcePath );

        // 2. Neue Maße berechnen (Proportionen erhalten)
        if ( $width > $maxWidth )
        {
            $ratio = $width / $height;
            $newWidth = $maxWidth;
            $newHeight = (int)( $maxWidth / $ratio );
        }
        else
        {
            $newWidth = $width;
            $newHeight = $height;
        }

        // 3. Ressource je nach Typ erstellen
        $source = match ( $type )
        {
            IMAGETYPE_JPEG => imagecreatefromjpeg( $sourcePath ),
            IMAGETYPE_PNG  => imagecreatefrompng( $sourcePath ),
            IMAGETYPE_WEBP => imagecreatefromwebp( $sourcePath ),
            default        => null
        };

        if ( $source )
        {
            $newImage = imagecreatetruecolor( $newWidth, $newHeight );

            // Transparenz-Handling (falls PNG/WEBP)
            imagealphablending( $newImage, false );
            imagesavealpha( $newImage, true );

            // Skalieren
            imagecopyresampled( $newImage, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height );

            // Speichern als JPG (effizienteste Kompression für Fotos)
            imagejpeg( $newImage, $targetPath, $quality );

            // Speicher freigeben
            imagedestroy( $source );
            imagedestroy( $newImage );
        }
    }

}
