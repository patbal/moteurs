<?php

namespace App\Controller;

use App\Entity\CarnetMoteur;
use App\Entity\Moteur;
use App\Entity\TypeMateriel;
use App\Form\CarnetMoteurType;
use DateTime;
use Endroid\QrCode\QrCode;
use phpDocumentor\Reflection\Types\Array_;
use function PHPSTORM_META\type;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Tests\Compiler\F;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

//TODO: développer un menu dynamique
//TODO: refaire le contrôleur de chaque catégorie

class MoteursController extends AbstractController
{
    /**
     * @IsGranted("ROLE_USER")
     * @Route("/", name="index")
     */
    public function index(SessionInterface $session)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if($session -> has('menuItemsGenerated'))       //on voit si le menu a déjà été généré
        {
            $menuItems = $session->get('menuItems');
        }
        Else{
            $tousAppareils = $this -> getDoctrine() -> getRepository(Moteur::class) -> findAll();
            $typeMaterielRef = '';
            $menuItems = array();

            foreach ($tousAppareils as $appareil) {
                $typeMateriel = $appareil->getType()->getNomComplet();
                $serie = $appareil->getTypeMoteur();

                if ($typeMateriel !== $typeMaterielRef) {
                    $listeType[] = $typeMateriel;
                    if (!isset($$typeMateriel)) {
                        $$typeMateriel = array();
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
            $session -> set('menuItemsGenerated', true);
            $session -> set('menuItems', $menuItems);
        }

        if(($session->has('menuReload')) && $session->get('menuReload')){
            $session->set('menuReload', false);
            return $this -> redirectToRoute('index');
        }



        return $this->render('moteurs/index.html.twig', [
            'controller_name' => 'HomePage',
            'titre' => 'Carnet Moteurs',
            'user' => $user,
            'menuItems' => $menuItems,
        ]);
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/gestion", name="gestion")
     */
    public function gestion() {

        return $this->render('moteurs/gestionQRCodes.html.twig', [
            'controller_name' => 'MoteursController',
        ]);
    }

    /**
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @Route("/genqr", name="genQRCodes")
     */
    public function gen_qr_code(SessionInterface $session)
    {
        $session ->remove('menuItemsGenerated');
        $em = $this -> getDoctrine() -> getManager();

        //on scanne le répertoire de dépose pour connaître tous les répertoires présents
        $repertoires = new finder();
        $repertoires -> directories() -> in('certificats')->sortByName(true);

        foreach ($repertoires as $rep){
            $files = new Finder();
            $files -> files() -> name('*.pdf') -> in("$rep") -> sortByName(true); //on liste les fichiers du répertoire

            //s'il y a des fichiers dans le répertoire, on injecte leur objet dans un tableau
            if(count($files)>0){
                foreach ($files as $file){
                    ${$rep}[] = $file;                            //le tableau prend le nom du répertoire scanné, et chaque objet est stocké dans le tableau
                }
                $nomRepertoire = $rep->getFilename();           //on récupère le nom de la clef du tableau
                $arborescence["$nomRepertoire"] = $$rep;        //et on injecte les objets Finder fichier du répertoire dans le tableau
            }

            else{
                $nomRep = $rep -> getFilename();
                $this -> addFlash('alert', 'le répertoire "'.$nomRep.'" ne contient pas de fichier');
            }
        }

        $listeDir = array_keys($arborescence);                      //Récupération du noms des répertoires
        $fileSystem = new Filesystem();                             //on crée une instance de Filesystem pour créer les répertoires

        foreach ($listeDir as $dir){
            $fileSystem -> mkdir("images/test/$dir");          //on crée les répertoires dans le dossier image, où seront stockés les QrCodes
        }

        foreach ($arborescence as $dir => $files){                  //Dans chaque répertoire source,
            $typeMat = array();
            $typeMat = explode('_', $dir);

            //on récupère l'objet typeMateriel pour la requête dans l'objet moteur
            $typeMateriel = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy([
                'type' => $typeMat[0],
            ]);

            foreach ($files as $file){                              //Pour chaque fichier
                $nomFichierSansExtension = trim(($file -> getFilename()), '.pdf');

                $qrCode = new QrCode();                             //on crée le QRCode
                //test pour savoir si on encode en local ou sur le serveur de déploiement
                if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false)
                {
                    $urlPv = "/certificats/$dir/".($file -> getFilename());
                }
                else
                {
                    $urlPv = 'http://'.$_SERVER['HTTP_HOST']."/certificats/$dir/".($file -> getFilename());
                }
                $qrCode->setText($urlPv);
                $qrCode->setSize(200);
                $qrCode->writeFile("images/test/$dir/".$nomFichierSansExtension.'.png');    //et on écrit le fichier

                //on récupère le moteur dans la base s'il y est
                $moteur = $this -> getDoctrine() -> getRepository(Moteur::class) -> findOneBy([
                    'numeroMoteur' => $nomFichierSansExtension,
                ]);

                if(!$moteur) //s'il n'est pas dans la base on le crée
                {
                    $moteur = new Moteur();
                    $moteur -> setTypeMoteur($typeMat[1]);
                    $moteur -> setEnService(true);
                    $moteur -> setNumeroMoteur($nomFichierSansExtension);
                    $moteur -> setType($typeMateriel);
                }

                // s'il y était on ne change que l'URL de son PV et celui du QRCode associé
                $moteur -> setUrlQRCode('/images/qrcodes/'.$dir.'/'.$nomFichierSansExtension.'.png');
                $moteur -> setUrlPV($urlPv);

                $em -> persist($moteur);
                $em -> flush();
            }
        }

        $session -> set('menuReload', true);

        $this -> addFlash('notice', 'QRCodes des moteurs générés');
        return $this -> redirectToRoute('index');

    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/view/qrCodes/{cat}", name="viewQrCodes")
     * @param string $cat
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function viewQrCodes(string $cat, SessionInterface $session)
    {
        // on store $cat dans la session afin de retrouver la route en cas de suppression
        $session->set('categorie', $cat);

        //liste des valeurs que peut prendre $cat
        $vals = array('m250', 'm500', 'm1000', 'm2000','mall', 'sc400', 'sc500', 'sc1000', 'scall');

        //si $cat n'st pas dans l'array, -> erreur
        if(!in_array($cat, $vals))
        {
            $this->addFlash('alert', 'Les valeurs personnalisées ne sont pas autorisées dans la barre d\'adresse');
            return $this->redirectToRoute('index');
        }

        //si $cat ne contient pas "all", on cherche les moteurs ou sc de la catégorie $cat
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
                'enService' => true],
                ['typeMoteur' => 'ASC',
                 'numeroMoteur' => 'ASC'
            ]);
        }

        //si $cat contient "all", on affiche tous les moteurs ou sc
        if (preg_match('/[all]/', $cat))
        {
            $type = preg_filter('/(m|sc)(\w*)/','$1', $cat);
            $typeEl = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy(
                [
                    'type' => $type,
                ]
            );
            $moteurs = $this -> getDoctrine() -> getRepository(Moteur::class) -> findBy([
                'type' => $typeEl,
                'enService' => true],
                ['typeMoteur' => 'ASC',
                'numeroMoteur' => 'ASC'
            ]);
            $charge = '';
        }

        $lev = ($type == 'm') ? 'moteur' : 'stop-chute' ; //pour passage de paramètre à twig

        return $this -> render('moteurs/viewQrCodes2.html.twig', array(
            'moteurs' => $moteurs,
            'cat' => $cat,
            'charge' => $charge,
            'lev' => $lev,
        ));

    }

    /**
     * @IsGranted("ROLE_ADMIN")
     * @Route("/print/qrCodes/{cat}", name="printQrCodes")
     */
    public function printQrCodes(string $cat)
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
                'typeMoteur' => $charge],[
                'numeroMoteur' => 'ASC',
            ]);
        }

        //si $cat contient "all", on affiche tous les moteurs ou sc
        if (preg_match('/[all]/', $cat))
        {
            $type = preg_filter('/(m|sc)(\w*)/','$1', $cat);
            $typeEl = $this -> getDoctrine() -> getRepository(TypeMateriel::class) -> findOneBy(
                [
                    'type' => $type,
                ]
            );
            $moteurs = $this -> getDoctrine() -> getRepository(Moteur::class) -> findBy([
                'type' => $typeEl],[
                'numeroMoteur' => 'ASC',
            ]);
            $charge = '';
        }

        $lev = ($type == 'm') ? 'moteur' : 'stop-chute' ; //pour passage de paramètre à twig

        return $this -> render('moteurs/impressionQRCodes.html.twig', array(
            'moteurs' => $moteurs,
            'cat' => $cat,
            'charge' => $charge,
            'lev'=> $lev,
        ));

    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("view/moteur/{id}", name="viewMotor")
     */
    public function viewMotor(Moteur $moteur, Request $request, $id)
    {
        if (null === $moteur)
        {
            throw new NotFoundHttpException("Ce moteur n'existe pas en base de donnée. Veuillez vérifier le numéro, ou régénérer la base de donnée.");
        }

        // on récupère le carnet du moteur
        $carnet = $this -> getDoctrine() -> getRepository(CarnetMoteur::class) -> findBy(
            ['moteur' => $moteur->getId()],
            ['date' => 'DESC']
        );

        return $this->render('moteurs/viewMotor.html.twig', array(
            'moteur' => $moteur,
            'carnet' => $carnet,
        ));

    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("add/carnet/{id}", name="addEntreeCarnet")
     */
    public function addCarnet(Moteur $motorId, Request $request)
    {
        $entree = new CarnetMoteur();
        $entree -> setMoteur($motorId);
        $dateNow = new DateTime();
        $entree -> setDate($dateNow);

        $moteur = $this -> getDoctrine() -> getRepository(Moteur::class) -> findOneBy(['id'=>$motorId]);

        $form = $this -> get('form.factory') -> create(CarnetMoteurType::class, $entree);
        $em = $this -> getDoctrine() -> getManager();

        if ($request->isMethod('POST') && $form -> handleRequest($request)->isValid())
        {
            $user = $this->getUser();
            $entree -> setUser($user);
            $entree -> setMoteur($moteur);
            $this -> addFlash('notice', 'opération ajoutée au carnet d\'entretien');
            $em -> persist($entree);
            $em -> flush();
            return $this -> redirectToRoute('viewMotor', array('id' => $moteur->getId()));
        }

        return $this -> render('moteurs/addEntreeCarnet.html.twig', array(
            'form' => $form -> createView(),
            'moteur' => $moteur,
            'entree' => $entree,
        ));
    }

    /**
     * @IsGranted("ROLE_USER")
     *@Route("view/carnet/{id}", name="viewEntreeCarnet")
     */
    public function viewEntreeCarnet(CarnetMoteur $carnet, $id)
    {
        $moteur = $carnet -> getMoteur();

        return $this -> render('moteurs/viewEntreeCarnet.html.twig', array(
            'entree' => $carnet,
            'moteur' => $moteur,
        ));
    }

    /**
     * @IsGranted("ROLE_ADMIN")
     *@Route("edit/carnet/{id}", name="editEntreeCarnet")
     */
    public function editEntreeCarnet(CarnetMoteur $entree, $id, Request $request)
    {
        $moteur = $entree -> getMoteur();

        $form = $this -> get('form.factory') -> create(CarnetMoteurType::class, $entree);
        $em = $this -> getDoctrine() -> getManager();

        if ($request->isMethod('POST') && $form -> handleRequest($request)->isValid())
        {
            $this -> addFlash('notice', 'Mise à jour effectuée');
            $em -> persist($entree);
            $em -> flush();
            return $this -> redirectToRoute('viewMotor', array('id' => $moteur->getId()));
        }

        return $this -> render('moteurs/addEntreeCarnet.html.twig', array(
            'form' => $form -> createView(),
            'moteur' => $moteur,
            'entree' => $entree,
        ));
    }

    /**
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @Route("delete/carnet/{id}", name="deleteEntreeCarnet")
     */
    public function deleteEntreeCarnet(CarnetMoteur $entree, $id)
    {
        $moteur = $this -> getDoctrine() -> getRepository(Moteur::class) -> find($entree->getMoteur());
        $em = $this -> getDoctrine() -> getManager();
        $em -> remove($entree);
        $em -> flush();

        return $this->redirectToRoute('viewMotor', array(
            'id' => $moteur -> getId(),
        ));
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        // controller can be blank: it will never be executed!
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @IsGranted("ROLE_SUPER_ADMIN")
     * @Route("delete/moteur/{id}", name="deleteMoteur")
     * @param Moteur $moteur
     * @param $id
     * @param SessionInterface $session
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function deleteMoteur(Moteur $moteur, $id, SessionInterface $session)
    {
        if (null === $moteur)
        {
            throw new NotFoundHttpException("Ce moteur n'existe pas en base de donnée. Veuillez vérifier le numéro, ou régénérer la base de donnée.");
        }

        $em = $this -> getDoctrine() -> getManager();
        $em -> remove($moteur);
        $em -> flush();

        $session = new Session();
        $this -> addFlash('notice', 'Le moteur '.$moteur->getNumeroMoteur().' a été supprimé de la base de données');

        return $this -> redirectToRoute('viewQrCodes', array(
            'cat' => ($session -> get('categorie')),
            ));
    }

    /**
     * IsGranted("ROLE_ADMIN")
     * @Route("/deactivate/motor/{id}", name="deactivateMoteur")
     */
    public function deactivate(Moteur $moteur, $id)
    {
        if (null === $moteur)
        {
            throw new NotFoundHttpException("Ce moteur n'existe pas en base de donnée. Veuillez vérifier le numéro, ou régénérer la base de donnée.");
        }

        $em = $this -> getDoctrine() -> getManager();
        $moteur -> setEnService(false);
        $em -> persist($moteur);
        $em -> flush();

        $this -> addFlash('warning', 'Le moteur '.$moteur->getNumeroMoteur().' a été désactivé');

        return $this -> redirectToRoute('viewMotor', array(
            'id' => $id));
    }

    /**
     * IsGranted("ROLE_ADMIN")
     * @Route("/activate/motor/{id}", name="activateMoteur")
     */
    public function activate(Moteur $moteur, $id)
    {
        if (null === $moteur)
        {
            throw new NotFoundHttpException("Ce moteur n'existe pas en base de donnée. Veuillez vérifier le numéro, ou régénérer la base de donnée.");
        }

        $em = $this -> getDoctrine() -> getManager();
        $moteur -> setEnService(true);
        $em -> persist($moteur);
        $em -> flush();

        $this -> addFlash('notice', 'Le moteur '.$moteur->getNumeroMoteur().' a été activé');

        return $this -> redirectToRoute('viewMotor', array(
            'id' => $id));
    }

    /**
     * @IsGranted("ROLE_USER")
     * @Route("/view/deactivated", name="viewDeactivated")
     */
    public function viewDeactivated()
    {
        $moteurs = $this -> getDoctrine() -> getRepository(Moteur::class) -> findBy(
            ['enService' => false],
            ['numeroMoteur' => 'ASC']
        );

        return $this -> render('moteurs/viewDeactivated.html.twig', array(
            'moteurs' => $moteurs,
        ));

    }
}

