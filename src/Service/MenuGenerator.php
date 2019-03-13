<?php
/**
 * Created by PhpStorm.
 * User: p.balland
 * Date: 2019-03-13
 * Time: 15:22
 */

namespace App\Service;


use Symfony\Component\HttpFoundation\Session\SessionInterface;

class MenuGenerator
{
    private $appareils;

    public function __construct(array $appareils)
    {
        $this -> appareils = $appareils;
    }

    public function generateMenu(){
        $typeMaterielRef = '';
        $menuItems = array();

        $tousAppareils = $this -> appareils;

        foreach ($tousAppareils as $appareil) {
            $typeMateriel = $appareil -> getType()->getNomComplet();
            $serie = $appareil->getTypeMoteur();

            if ($typeMateriel !== $typeMaterielRef) {
                $listeType[] = $typeMateriel;
                if (!isset($$typeMateriel)) {
                    $$typeMateriel = array();
                    ${$typeMateriel}[] = $typeMateriel;
                }
                $typeMaterielRef = $typeMateriel;
            }

            if (!in_array($serie, $$typeMateriel)) {
                ${$typeMateriel}[] = $serie;
            }
        }

        foreach ($listeType as $type){
            if(isset($$type)){
                $menuItems[] = $$type;
            }
        }

        return $menuItems;
    }

}