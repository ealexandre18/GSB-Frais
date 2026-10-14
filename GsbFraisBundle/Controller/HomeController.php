<?php
namespace Pg\GsbFraisBundle\Controller;
require_once("include/fct.inc.php");
//require_once ("include/class.pdogsb.inc.php");
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
//use PdoGsb;

class HomeController extends Controller
{
    public function indexAction()
    {
       $session= $this->get('request')->getSession();
       if(estConnecte($session)){
              return $this->render('PgGsbFraisBundle:accueil.html.twig');
            }
        else 
              return $this->render('PgGsbFraisBundle:Home:connexion.html.twig');
    }
    public function validerconnexionAction(){
        $session= $this->get('request')->getSession();
        $request = $this->get('request');
        $login =  $request->request->get('login');
        $mdp = $request->request->get('mdp');
        //$pdo = PdoGsb::getPdoGsb();
        $pdo = $this->get('pg_gsb_frais.pdo');
        $visiteur = $pdo->getInfosVisiteur($login,$mdp);
        if(!is_array($visiteur)){
            // Si ce n'est pas un visiteur, on regarde si c'est un comptable
            $comptable = $pdo->getInfosComptable($login,$mdp);
            if(!is_array($comptable)){
                $admin = $pdo->getInfosAdmin($login,$mdp);
                if(!is_array($admin)){
                    return $this->render('PgGsbFraisBundle:Home:connexion.html.twig',array(
                       'message'=>'Erreur de login ou de mot de passe ')); 
                }
                else{
                    $session->set('id',$admin['id']);
                    $session->set('nom',$admin['nom']);
                    $session->set('prenom',$admin['prenom']);
                    $session->set('role','admin');
                    $lesVisiteurs = $pdo->getTousLesVisiteurs();
                    $lesComptables = $pdo->getTousLesComptables();
                    return $this->render('PgGsbFraisBundle:Admin:gestionutilisateurs.html.twig', [
                        'lesVisiteurs' => $lesVisiteurs,
                        'lesComptables' => $lesComptables
                    ]);
                }
            }
            else{
                $session->set('id',$comptable['id']);
                $session->set('nom',$comptable['nom']);
                $session->set('prenom',$comptable['prenom']);
                $session->set('role','comptable');
                return $this->render('PgGsbFraisBundle::accueil.html.twig');
            }
        }
        else{
            $session->set('id',$visiteur['id']);
            $session->set('nom',$visiteur['nom']);
            $session->set('prenom',$visiteur['prenom']);
            $session->set('role','visiteur');
            return $this->render('PgGsbFraisBundle::accueil.html.twig');
        }
    } 
    public function deconnexionAction(){
        $session = $this->get('request')->getSession();
        $session->clear();
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        return $this->render('PgGsbFraisBundle:Home:connexion.html.twig');
    }
}
?>