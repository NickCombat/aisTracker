<?php
// src/Service/SettingsService.php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\NetSeitenParameterRepository;

class SettingsService
{

    private array $cache = [];

    public function __construct(
        private NetSeitenParameterRepository $einstellungenRepository,
        private ParameterBagInterface $params
    )
    {}

    public function get( string $key ): mixed
    {
        if ( array_key_exists( $key, $this->cache ) )
        {
            return $this->cache[ $key ];
        }

        $einstellung = $this->einstellungenRepository->findOneBy( [ 'name' => $key ] );

        if ( $einstellung !== null )
        {
            $this->cache[ $key ] = $einstellung->getWert();

            return $this->cache[ $key ];
        }

        try
        {
            $value = $this->params->get( $key );
            $this->cache[ $key ] = $value;

            return $value;
        }
        catch ( \Exception $e )
        {
            return null;
        }
    }
}