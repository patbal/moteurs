<?php

namespace App\Controller;

use App\Entity\Moteur;
use App\Entity\TypeMateriel;
use Endroid\QrCode\QrCode;
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
                    $typeMat = array('m', '1000kg');
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

                    $moteur = new Moteur(); //préparation de l'objet moteur pour créer la base de donnée

                    $moteur -> setUrlPV($url);
                    $moteur -> setTypeMoteur($typeMat[1]);
                    $moteur -> setType($typeMateriel);
                    $moteur -> setEnService(true);
                    $moteur -> setNumeroMoteur($nom_fichier_sans_extension);
                    $moteur -> setUrlQRCode(__DIR__);

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
        $page = $request -> get('page');

        if(!in_array($cat, $vals))
        {
            $this->addFlash('alert', 'Les valeurs personnalisées ne sont pas autorisées dans la barre d\'adresse');
            return $this->redirectToRoute('index');
        }

        $finder = new Finder();

        if ($cat=='mall')
        {
            $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/m*/');
        }

        elseif ($cat=='scall')
        {
            $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/sc*/');
        }

        else{
            $finder->files()->name('*.png') -> sortByName(true) -> in('images/qrcodes/'.$cat);
        }

        return $this->render('moteurs/'.$page.'QrCodes.html.twig', array(
            'files' => $finder,
            'cat' => $cat,
        ));
    }
}
