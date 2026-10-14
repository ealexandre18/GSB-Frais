<?php
namespace Pg\GsbFraisBundle\Controller;

require_once("include/fct.inc.php");
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminController extends Controller
{
    public function indexAction()
    {
        $session = $this->get('request')->getSession();
        $pdo = $this->get('pg_gsb_frais.pdo');
        
        $session->set('id', 'admin1');
        $session->set('nom', 'Système');
        $session->set('prenom', 'Administrateur');
        $session->set('role', 'admin');

        $lesVisiteurs = $pdo->getTousLesVisiteurs();
        $lesComptables = $pdo->getTousLesComptables();

        return $this->render('PgGsbFraisBundle:Admin:gestionutilisateurs.html.twig', [
            'lesVisiteurs' => $lesVisiteurs,
            'lesComptables' => $lesComptables
        ]);
    }

    public function creercompteAction()
    {
        $session = $this->get('request')->getSession();
        $request = $this->get('request');
        $pdo = $this->get('pg_gsb_frais.pdo');
        
        $session->set('id', 'admin1');
        $session->set('nom', 'Système');
        $session->set('prenom', 'Administrateur');
        $session->set('role', 'admin');

        $message = null;
        $typeMessage = null;

        if ($request->getMethod() == 'POST') {
            $role = $request->request->get('type_compte');
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $login = $request->request->get('login');
            $mdp = $request->request->get('mdp');
            
            if ($role === 'visiteur') {
                $id = strtolower(substr($nom, 0, 1) . rand(10, 99));
                $adresse = $request->request->get('adresse') ?? '';
                $cp = $request->request->get('cp') ?? '';
                $ville = $request->request->get('ville') ?? '';
                $dateEmbauche = date('Y-m-d');
                $idVehicule = $request->request->get('id_vehicule') ?? 3;

                $pdo->creerVisiteur($id, $nom, $prenom, $login, $mdp, $adresse, $cp, $ville, $dateEmbauche, $idVehicule);
                $message = "Le compte Visiteur Médical pour " . htmlspecialchars($prenom . ' ' . $nom) . " a été créé avec succès (Identifiant : " . $id . ").";
                $typeMessage = "success";
            } else if ($role === 'comptable') {
                $id = (string)rand(10, 99);
                $pdo->creerComptable($id, $nom, $prenom, $login, $mdp);
                $message = "Le compte Agent Comptable pour " . htmlspecialchars($prenom . ' ' . $nom) . " a été créé avec succès (Identifiant : " . $id . ").";
                $typeMessage = "success";
            }
        }

        return $this->render('PgGsbFraisBundle:Admin:creercompte.html.twig', [
            'message' => $message,
            'typeMessage' => $typeMessage
        ]);
    }

    public function modifiercompteAction()
    {
        $session = $this->get('request')->getSession();
        $request = $this->get('request');
        $pdo = $this->get('pg_gsb_frais.pdo');
        
        $session->set('id', 'admin1');
        $session->set('nom', 'Système');
        $session->set('prenom', 'Administrateur');
        $session->set('role', 'admin');

        $type = $request->query->get('type', $request->request->get('type', 'visiteur'));
        $id = $request->query->get('id', $request->request->get('id'));
        $message = null;

        if ($request->getMethod() == 'POST') {
            $nom = $request->request->get('nom');
            $prenom = $request->request->get('prenom');
            $login = $request->request->get('login');

            if ($type === 'visiteur') {
                $adresse = $request->request->get('adresse') ?? '';
                $cp = $request->request->get('cp') ?? '';
                $ville = $request->request->get('ville') ?? '';
                $idVehicule = $request->request->get('id_vehicule') ?? 3;
                $pdo->modifierVisiteur($id, $nom, $prenom, $login, $adresse, $cp, $ville, $idVehicule);
            } else {
                $pdo->modifierComptable($id, $nom, $prenom, $login);
            }
            $message = "Les informations du compte " . htmlspecialchars($prenom . ' ' . $nom) . " ont été modifiées avec succès.";
        }

        $utilisateur = ($type === 'comptable') ? $pdo->getComptableParId($id) : $pdo->getVisiteurParId($id);

        return $this->render('PgGsbFraisBundle:Admin:modifiercompte.html.twig', [
            'utilisateur' => $utilisateur,
            'type' => $type,
            'message' => $message
        ]);
    }

    public function reinitmdpAction()
    {
        $session = $this->get('request')->getSession();
        $request = $this->get('request');
        $pdo = $this->get('pg_gsb_frais.pdo');

        $session->set('id', 'admin1');
        $session->set('nom', 'Système');
        $session->set('prenom', 'Administrateur');
        $session->set('role', 'admin');

        $type = $request->query->get('type', 'visiteur');
        $id = $request->query->get('id');

        $pdo->reinitialiserMdp($type, $id, 'secret');
        $message = "Le mot de passe de l'utilisateur (ID: " . htmlspecialchars($id) . ") a été réinitialisé à 'secret'.";

        $lesVisiteurs = $pdo->getTousLesVisiteurs();
        $lesComptables = $pdo->getTousLesComptables();

        return $this->render('PgGsbFraisBundle:Admin:gestionutilisateurs.html.twig', [
            'lesVisiteurs' => $lesVisiteurs,
            'lesComptables' => $lesComptables,
            'messageFlash' => $message
        ]);
    }

    public function togglestatutAction()
    {
        $session = $this->get('request')->getSession();
        $request = $this->get('request');
        $pdo = $this->get('pg_gsb_frais.pdo');

        $session->set('id', 'admin1');
        $session->set('nom', 'Système');
        $session->set('prenom', 'Administrateur');
        $session->set('role', 'admin');

        $type = $request->query->get('type', 'visiteur');
        $id = $request->query->get('id');

        $nouveauStatut = $pdo->toggleStatutUtilisateur($type, $id);
        $message = "Le statut du compte (ID: " . htmlspecialchars($id) . ") est désormais : " . htmlspecialchars($nouveauStatut);

        $lesVisiteurs = $pdo->getTousLesVisiteurs();
        $lesComptables = $pdo->getTousLesComptables();

        return $this->render('PgGsbFraisBundle:Admin:gestionutilisateurs.html.twig', [
            'lesVisiteurs' => $lesVisiteurs,
            'lesComptables' => $lesComptables,
            'messageFlash' => $message
        ]);
    }
}
?>
