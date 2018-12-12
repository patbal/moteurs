<?php

namespace App\Controller;

use Endroid\QrCode\QrCode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
     * @Route("/qr/{name}", name="qr")
     */
    public function gen_qr_code($name)
    {
        $qrCode = new QrCode();
        $qrCode->setText('horloge dieu sinistre effrayant impassible');
        $qrCode->setSize(200);

        // Save it to a file
        //$qrCode->writeFile(__DIR__.'/'.$name.'.png');
        $qrCode->writeFile('images/qrcodes/' . $name . '.png');

        // Directly output the QR code
        //header('Content-Type: '.$qrCode->getContentType());
        //echo $qrCode->writeString();

    }

    /**
     * @Route("/view/qrCodes/{cat}", name="viewQrCodes")
     */
    public function viewQrCodes($cat)
    {
        $finder=new Finder();
        $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/'.$cat);

        return $this->render('moteurs/affichageQrCodes.html.twig', array(
            'files' => $finder,
            'cat' => $cat
        ));
    }
}
