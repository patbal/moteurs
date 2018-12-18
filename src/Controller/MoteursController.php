<?php

namespace App\Controller;

use App\Entity\Moteur;
use App\Entity\TypeMateriel;
use Endroid\QrCode\QrCode;
use PhpParser\Node\Stmt\ElseIf_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Tests\Compiler\F;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/gestion", name="gestion")
     */
    public function gestion()
    {
        return $this->render('moteurs/gestionQRCodes.html.twig', [
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
        $mot2000 = new Finder();
        $mot250->files()->name('*.pdf') -> in('certificats/m250/');
        $mot500->files()->name('*.pdf') -> in('certificats/m500/');
        $mot1000->files()->name('*.pdf') -> in('certificats/m1000/');
        $mot2000->files()->name('*.pdf') -> in('certificats/m2000/');
        $sc400 = new Finder();
        $sc500 = new Finder();
        $sc1000 = new Finder();
        $sc400->files()->name('*.pdf') -> in('certificats/sc400/');
        $sc500->files()->name('*.pdf') -> in('certificats/sc500/');
        $sc1000->files()->name('*.pdf') -> in('certificats/sc1000/');

        $types = array($mot250, $mot500, $mot1000, $mot2000, $sc400, $sc500, $sc1000);

        $em = $this -> getDoctrine() -> getManager();

        //On boucle sur les valeurs des objets files
        foreach ($types as $type)
        {
            if (!empty($type))

            //On détermine une variable "rep" pour écrire plus tard l'image dans le bon répertoire
            switch ($type)
            {
                case $mot250 :
                    $rep = 'm250';
                    $typeMat = array('m', '250kg');
                    break;
                case $mot500 :
                    $rep = 'm500';
                    $typeMat = array('m', '500kg');
                    break;
                case $mot1000 :
                    $rep = 'm1000';
                    $typeMat = array('m', '1000kg');
                    break;
                case $mot2000 :
                    $rep = 'm2000';
                    $typeMat = array('m', '2000kg');
                    break;
                case $sc400 :
                    $rep = 'sc400';
                    $typeMat = array('sc', '400kg');
                    break;
                case $sc500 :
                    $rep = 'sc500';
                    $typeMat = array('sc', '500kg');
                    break;
                case $sc1000 :
                    $rep = 'sc1000';
                    $typeMat = array('sc', '1000kg');
                    break;
            }
            {
                foreach ($type as $element)
                {
                    $nom_fichier = $element -> getFilename();
                    $nom_fichier_sans_extension = trim($nom_fichier, '.pdf');
                    $url = $element -> getRealPath();
                    //on crée le QRCode
                    $qrCode = new QrCode();
                    $qrCode->setText($url);
                    $qrCode->setSize(200);
                    //et on écrit le fichier
                    $qrCode->writeFile('images/qrcodes/'.$rep.'/' . $nom_fichier_sans_extension . '.png');

                    //on s'occupe de la bdd moteur
                    $typeMateriel = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy([
                        'type' => $typeMat[0],
                    ]);

                    //on récupère le moteur dans la base s'il y est
                    $moteur = $this -> getDoctrine() -> getRepository(Moteur::class) -> findOneBy([
                        'numeroMoteur' => $nom_fichier_sans_extension,
                    ]);

                    if(!$moteur) //s'il n'est pas dans la base on le crée
                    {
                        $moteur = new Moteur();
                    }

                    //$moteur -> setUrlPV($url);
                    $moteur -> setTypeMoteur($typeMat[1]);
                    $moteur -> setType($typeMateriel);
                    $moteur -> setEnService(true);
                    $moteur -> setNumeroMoteur($nom_fichier_sans_extension);
                    $moteur -> setUrlQRCode('/images/qrcodes/'.$rep.'/'.$nom_fichier_sans_extension.'.png');
                    $moteur -> setUrlPV('/certificats/'.$rep.'/'.$nom_fichier);

                    $em -> persist($moteur);
                    $em -> flush();

                }
            }
        }

        $this -> addFlash('notice', 'QRCodes des moteurs générés');
        return $this -> redirectToRoute('gestion');

    }

    /**
     * @Route("/view/qrCodes/{cat}", name="viewQrCodes")
     */
    public function viewQrCodes(Request $request, string $cat)
    {

        $vals = array('m250', 'm500', 'm1000', 'm2000','mall', 'sc400', 'sc500', 'sc1000', 'scall');

        if(!in_array($cat, $vals))
        {
            $this->addFlash('alert', 'Les valeurs personnalisées ne sont pas autorisées dans la barre d\'adresse');
            return $this->redirectToRoute('index');
        }

        /*
         * ///////////////////////////////////////////////////////
         * il faut traiter le cas où $cat contient "all"
         * ///////////////////////////////////////////////////////
         */

        if (!preg_match('/[all]/', $cat))
        {
            $capa = preg_filter('/([a-z]+)(\d*)/', '$2', $cat);
            $type = preg_filter('/([a-z]+)(\d*)/','$1', $cat);
            $charge = $capa.'kg';
            $typeEl = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy(
                [
                    'type' => $type,
                ]
            );
            $moteurs = $this -> getDoctrine() -> getRepository(Moteur::class) -> findBy([
                'type' => $typeEl,
                'typeMoteur' => $charge,
            ]);
        }

        return $this -> render('moteurs/viewQrCodes2.html.twig', array(
            'moteurs' => $moteurs,
            'cat' => $cat,
            'charge' => $charge,
        ));

    }

    /**
     * @Route("/print/qrCodes/{cat}", name="printQrCodes")
     */
    public function printQrCodes(Request $request, string $cat)
    {

        $vals = array('m250', 'm500', 'm1000', 'm2000','mall', 'sc400', 'sc500', 'sc1000', 'scall');

        if(!in_array($cat, $vals))
        {
            $this->addFlash('alert', 'Les valeurs personnalisées ne sont pas autorisées dans la barre d\'adresse');
            return $this->redirectToRoute('index');
        }

        if (!preg_match('/[all]/', $cat))
        {
            $capa = preg_filter('/([a-z]+)(\d*)/', '$2', $cat);
            $type = preg_filter('/([a-z]+)(\d*)/','$1', $cat);
            $charge = $capa.'kg';
            $typeEl = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy(
                [
                    'type' => $type,
                ]
            );
            $moteurs = $this -> getDoctrine() -> getRepository(Moteur::class) -> findBy([
                'type' => $typeEl,
                'typeMoteur' => $charge,
            ]);
        }

        return $this -> render('moteurs/impressionQRCodes.html.twig', array(
            'moteurs' => $moteurs,
            'cat' => $cat,
            'charge' => $charge,
        ));

    }
}
