<?php
namespace Pg\GsbFraisBundle\Controller;
require_once("include/fct.inc.php");
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;

class SaisirFraisController extends Controller
{
    public function indexAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') === 'comptable') {
            return $this->render('PgGsbFraisBundle:accueil.html.twig');
        }
        $idVisiteur = $session->get('id');
        $mois = getMois(date("d/m/Y"));
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);
        $pdo = $this->get('pg_gsb_frais.pdo');
        
        if ($pdo->estPremierFraisMois($idVisiteur, $mois)) {
            $pdo->creeNouvellesLignesFrais($idVisiteur, $mois);
        }
        
        $request = $this->get('request');
        $lesErreursForfaits = array();
        if ($this->get('request')->getMethod() == 'POST') {
            $lesFrais = $request->request->all()['lesFrais'] ?? ($_POST['lesFrais'] ?? array());
            if (lesQteFraisValides($lesFrais)) {
                $pdo->majFraisForfait($idVisiteur, $mois, $lesFrais);
            } else {
                $lesErreursForfaits[] = "Les valeurs des frais doivent être numériques";
            }
        }
        
        $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $mois);
        $unVehicule = $pdo->getVehiculeVisiteur($idVisiteur);
        
        return $this->render('PgGsbFraisBundle:SaisirFrais:saisirtouslesfrais.html.twig', array(
            'lesfraisforfait' => $lesFraisForfait,
            'lesfraishorsforfait' => $lesFraisHorsForfait,
            'nummois' => $numMois,
            'numannee' => $numAnnee,
            'leserreursforfait' => $lesErreursForfaits,
            'leserreurshorsforfait' => null,
            'unVehicule' => $unVehicule
        ));
    }

    public function validerfraishorsforfaitAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') === 'comptable') {
            return $this->render('PgGsbFraisBundle:accueil.html.twig');
        }
        $idVisiteur = $session->get('id');
        $mois = getMois(date("d/m/Y"));
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);
        $pdo = $this->get('pg_gsb_frais.pdo');
        $request = $this->get('request');
        $dateFrais = $request->request->get('dateFrais');
        $libelle = $request->request->get('libelle');
        $montant = $request->request->get('montant');
        $lesErreursHorsForfait = valideInfosFrais($dateFrais, $libelle, $montant);
        
        // Gestion de l'upload du justificatif
        $nomFichierJustificatif = null;
        if (isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../../web/uploads/justificatifs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = strtolower(pathinfo($_FILES['justificatif']['name'], PATHINFO_EXTENSION));
            $extensionsAutorisees = array('pdf', 'jpg', 'jpeg', 'png');
            if (in_array($extension, $extensionsAutorisees)) {
                $nomFichierJustificatif = 'justif_' . $idVisiteur . '_' . time() . '.' . $extension;
                move_uploaded_file($_FILES['justificatif']['tmp_name'], $uploadDir . $nomFichierJustificatif);
            } else {
                $lesErreursHorsForfait[] = "Le format du justificatif doit être PDF, JPG ou PNG.";
            }
        }
        
        if (count($lesErreursHorsForfait) == 0) {
            $pdo->creeNouveauFraisHorsForfait($idVisiteur, $mois, $libelle, $dateFrais, $montant, $nomFichierJustificatif);
            $nbJustifs = $pdo->getNbJustificatifsReels($idVisiteur, $mois);
            $pdo->majNbJustificatifs($idVisiteur, $mois, $nbJustifs);
        }
        
        $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $mois);
        $unVehicule = $pdo->getVehiculeVisiteur($idVisiteur);
        
        return $this->render('PgGsbFraisBundle:SaisirFrais:saisirtouslesfrais.html.twig', array(
            'lesfraisforfait' => $lesFraisForfait,
            'lesfraishorsforfait' => $lesFraisHorsForfait,
            'nummois' => $numMois,
            'numannee' => $numAnnee,
            'leserreursforfait' => null,
            'leserreurshorsforfait' => $lesErreursHorsForfait,
            'unVehicule' => $unVehicule
        ));
    }

    public function supprimerfraishorsforfaitAction($id)
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') === 'comptable') {
            return $this->render('PgGsbFraisBundle:accueil.html.twig');
        }
        $idVisiteur = $session->get('id');
        $mois = getMois(date("d/m/Y"));
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);
        $pdo = $this->get('pg_gsb_frais.pdo');
        
        if ($pdo->estValideSuppressionFrais($idVisiteur, $mois, $id)) {
            $pdo->supprimerFraisHorsForfait($id);
            $nbJustifs = $pdo->getNbJustificatifsReels($idVisiteur, $mois);
            $pdo->majNbJustificatifs($idVisiteur, $mois, $nbJustifs);
        } else {
            $response = new Response;
            $response->setContent("<h2>Page introuvable erreur 404 ");
            $response->setStatusCode(404);
            return $response;
        }
        
        $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $mois);
        $unVehicule = $pdo->getVehiculeVisiteur($idVisiteur);
        
        return $this->render('PgGsbFraisBundle:SaisirFrais:saisirtouslesfrais.html.twig', array(
            'lesfraisforfait' => $lesFraisForfait,
            'lesfraishorsforfait' => $lesFraisHorsForfait,
            'nummois' => $numMois,
            'numannee' => $numAnnee,
            'leserreursforfait' => null,
            'leserreurshorsforfait' => null,
            'unVehicule' => $unVehicule
        ));
    }

    public function ajouterjustificatifAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') === 'comptable') {
            return $this->render('PgGsbFraisBundle:accueil.html.twig');
        }
        $idVisiteur = $session->get('id');
        $mois = getMois(date("d/m/Y"));
        $numAnnee = substr($mois, 0, 4);
        $numMois = substr($mois, 4, 2);
        $pdo = $this->get('pg_gsb_frais.pdo');
        $request = $this->get('request');
        $idFrais = $request->request->get('idFrais') ?? ($_POST['idFrais'] ?? null);
        
        $lesErreursHorsForfait = array();
        if ($idFrais && isset($_FILES['justificatif']) && $_FILES['justificatif']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = __DIR__ . '/../../../../web/uploads/justificatifs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $extension = strtolower(pathinfo($_FILES['justificatif']['name'], PATHINFO_EXTENSION));
            $extensionsAutorisees = array('pdf', 'jpg', 'jpeg', 'png');
            if (in_array($extension, $extensionsAutorisees)) {
                $nomFichier = 'justif_' . $idVisiteur . '_' . time() . '.' . $extension;
                if (move_uploaded_file($_FILES['justificatif']['tmp_name'], $uploadDir . $nomFichier)) {
                    $pdo->majJustificatifFraisHorsForfait($idFrais, $nomFichier);
                    $nbJustifs = $pdo->getNbJustificatifsReels($idVisiteur, $mois);
                    $pdo->majNbJustificatifs($idVisiteur, $mois, $nbJustifs);
                }
            } else {
                $lesErreursHorsForfait[] = "Le format du justificatif doit être PDF, JPG ou PNG.";
            }
        }
        
        $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $mois);
        $unVehicule = $pdo->getVehiculeVisiteur($idVisiteur);
        
        return $this->render('PgGsbFraisBundle:SaisirFrais:saisirtouslesfrais.html.twig', array(
            'lesfraisforfait' => $lesFraisForfait,
            'lesfraishorsforfait' => $lesFraisHorsForfait,
            'nummois' => $numMois,
            'numannee' => $numAnnee,
            'leserreursforfait' => null,
            'leserreurshorsforfait' => $lesErreursHorsForfait,
            'unVehicule' => $unVehicule
        ));
    }
}
?>
