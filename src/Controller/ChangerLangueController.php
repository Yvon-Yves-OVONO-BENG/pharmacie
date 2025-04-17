<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ChangerLangueController extends AbstractController
{
    #[Route('/changer-langue/{locale}', name: 'changer_langue')]
    public function changerLangue($locale, Request $request): Response
    {
        // On stocke la langue dans la session
        $request->getSession()->set('_locale', $locale);

        # je récupère ma session
        $maSession = $request->getSession();

        if(!$maSession)
        {
            return $this->redirectToRoute("app_logout");
        }

        // On revient sur la page précédente
        return $this->redirect($request->headers->get('referer'));
    }
}
