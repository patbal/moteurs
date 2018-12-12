<?php

namespace App\Controller;

use Endroid\QrCode\QrCode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Tests\Compiler\F;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Routing\Annotation\Route;

class MoteursController extends AbstractController
{
    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->render('moteurs/index.html.twig', [
            'controller_name' => 'HomePage',
            'titre' => 'Carnet Moteurs'
        ]);
    }

    /**
     * @Route("/moteurs", name="mot")
     */
    public function mot()
    {
        return $this->render('moteurs/index.html.twig', [
            'controller_name' => 'MoteursController',
        ]);
    }

    /**
     * @Route("/genqr", name="genQRCodes")
     */
    public function gen_qr_code()
    {
        //on utilise Finder pour lire le répertoire des PDF
        $mot250 = new Finder();
        $mot500 = new Finder();
        $mot1000 = new Finder();
        $mot250->files()->name('*.pdf') -> in('certificats/250/');
        $mot500->files()->name('*.pdf') -> in('certificats/500/');
        $mot1000->files()->name('*.pdf') -> in('certificats/1000/');

        //on boucle sur chaque finder pour générer le QRCode correspondant
        if (!empty($mot250))
        {
            foreach ($mot250 as $moteur)
            {
                $nom_fichier = $moteur -> getFilename();
                $nom_fichier_sans_extension = trim($nom_fichier, '.pdf');
                $url = $moteur -> getRealPath();
                //on crée le QRCode
                $qrCode = new QrCode();
                $qrCode->setText($url);
                $qrCode->setSize(200);
                //et on écrit le fichier
                $qrCode->writeFile('images/qrcodes/250/' . $nom_fichier_sans_extension . '.png');
            }
        }

        if (!empty($mot500))
        {
            foreach ($mot500 as $moteur)
            {
                $nom_fichier = $moteur -> getFilename();
                $nom_fichier_sans_extension = trim($nom_fichier, '.pdf');
                $url = $moteur -> getRealPath();
                //on crée le QRCode
                $qrCode = new QrCode();
                $qrCode->setText($url);
                $qrCode->setSize(200);
                //et on écrit le fichier
                $qrCode->writeFile('images/qrcodes/500/' . $nom_fichier_sans_extension . '.png');
            }
        }

        if (!empty($mot1000))
        {
            foreach ($mot1000 as $moteur)
            {
                $nom_fichier = $moteur -> getFilename();
                $nom_fichier_sans_extension = trim($nom_fichier, '.pdf');
                $url = $moteur -> getRealPath();
                //on crée le QRCode
                $qrCode = new QrCode();
                $qrCode->setText($url);
                $qrCode->setSize(200);
                //et on écrit le fichier
                $qrCode->writeFile('images/qrcodes/1000/' . $nom_fichier_sans_extension . '.png');
            }
        }

        $this -> addFlash('success', 'QRCodes des moteurs générés');
        return $this -> redirectToRoute('mot');

    }

    /**
     * @Route("/view/qrCodes/{cat}", name="viewQrCodes")
     */
    public function viewQrCodes(string $cat)
    {
        $vals = array('250', '500', '1000', 'all');
        $isAll = False;

        if(!in_array($cat, $vals))
        {
            $this->addFlash('alert', 'Les valeurs personnalisées ne sont pas autorisées dans la barre d\'adresse');
            return $this->redirectToRoute('mot');
        }

        $finder = new Finder();

        if ($cat=='all')
        {
            $isAll = true;
            $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/*/');
        }

        else{
            $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/'.$cat);
        }

        return $this->render('moteurs/affichageQrCodes.html.twig', array(
            'files' => $finder,
            'cat' => $cat,
            'isAll' => $isAll
        ));
    }
}
