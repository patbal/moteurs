<?php

namespace App\Controller;

use App\Entity\Moteur;
use App\Service\MenuGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils, SessionInterface $session): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        //si menuItems n'est pas en session, ou si menuReload est true, on crée les menus
        if(!($session -> has('menuItems')) || ($session -> get('menuReload') == true)){
            $appareils = $this -> getDoctrine() -> getRepository(Moteur::class) -> findAll();

            $menu = new MenuGenerator($appareils);              //on crée le menu via le service MenuGenerator
            $menuItems = $menu -> generateMenu();

            $session -> set('menuItemsGenerated', true);
            $session -> set('menuItems', $menuItems);
            $this -> addFlash('notice', 'Les menus ont été mis à jour');
            $session->set('menuReload', false);
        }

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
}
