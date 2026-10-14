<?php
namespace Pg\GsbFraisBundle\Controller;

require_once("include/fct.inc.php");
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;

class ValiderFraisController extends Controller
{
    public function indexAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') !== 'comptable') {
            return $this->render('PgGsbFraisBundle:Home:connexion.html.twig', array(
                'message' => 'Accès réservé aux comptables'
            ));
        }

        $pdo = $this->get('pg_gsb_frais.pdo');
        $request = $this->get('request');

        $lesVisiteurs = $pdo->getTousLesVisiteurs();
        
        // Liste des 6 derniers mois
        $lesMois = array();
        for ($i = 0; $i < 6; $i++) {
            $timestamp = strtotime("-$i month");
            $cleMois = date('Ym', $timestamp);
            $numAnnee = date('Y', $timestamp);
            $numMois = date('m', $timestamp);
            $lesMois[$cleMois] = array(
                'mois' => $cleMois,
                'numAnnee' => $numAnnee,
                'numMois' => $numMois
            );
        }

        $idVisiteurSelectionne = $request->query->get('lstVisiteur', $request->request->get('lstVisiteur'));
        $moisSelectionne = $request->query->get('lstMois', $request->request->get('lstMois'));
        $message = null;
        $messageType = 'info';

        // 1. Action : Actualiser les frais forfaitisés
        if ($request->getMethod() == 'POST' && $request->request->get('action') === 'actualiserForfait') {
            $lesFrais = $request->request->all()['lesFrais'] ?? ($_POST['lesFrais'] ?? array());
            if (lesQteFraisValides($lesFrais)) {
                $pdo->majFraisForfait($idVisiteurSelectionne, $moisSelectionne, $lesFrais);
                $message = "Les frais forfaitisés ont été mis à jour avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur : les quantités doivent être des valeurs numériques.";
                $messageType = 'error';
            }
        }

        // 2. Action : Refuser un frais hors forfait
        if ($request->getMethod() == 'POST' && $request->request->get('action') === 'refuserHF') {
            $idFraisHF = $request->request->get('idFraisHF');
            $pdo->refuserFraisHorsForfait($idFraisHF);
            $message = "Le frais hors forfait a été marqué comme REFUSÉ.";
            $messageType = 'warning';
        }

        // 3. Action : Reporter un frais hors forfait
        if ($request->getMethod() == 'POST' && $request->request->get('action') === 'reporterHF') {
            $idFraisHF = $request->request->get('idFraisHF');
            $pdo->reporterFraisHorsForfait($idFraisHF, $idVisiteurSelectionne, $moisSelectionne);
            $message = "Le frais hors forfait a été reporté sur la fiche du mois suivant.";
            $messageType = 'info';
        }

        // 4. Action : Validation finale de la fiche
        if ($request->getMethod() == 'POST' && $request->request->get('action') === 'validerFicheFinale') {
            $nbJustificatifs = (int)$request->request->get('nbJustificatifs', 0);
            $pdo->validerFicheFrais($idVisiteurSelectionne, $moisSelectionne, $nbJustificatifs);
            $message = "La fiche de frais a été validée avec succès (État passé à Validée).";
            $messageType = 'success';
        }

        $ficheExiste = false;
        $lesFraisForfait = array();
        $lesFraisHorsForfait = array();
        $lesInfosFicheFrais = null;
        $unVehicule = null;
        $visiteurInfo = null;

        if ($idVisiteurSelectionne && $moisSelectionne) {
            $ficheInfo = $pdo->getLesInfosFicheFrais($idVisiteurSelectionne, $moisSelectionne);
            if ($ficheInfo) {
                $ficheExiste = true;
                $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteurSelectionne, $moisSelectionne);
                $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteurSelectionne, $moisSelectionne);
                $lesInfosFicheFrais = $ficheInfo;
                $unVehicule = $pdo->getVehiculeVisiteur($idVisiteurSelectionne);
                $visiteurInfo = $pdo->getInfosVisiteurParId($idVisiteurSelectionne);
            } else {
                $message = "Pas de fiche de frais pour ce visiteur ce mois.";
                $messageType = 'error';
            }
        }

        return $this->render('PgGsbFraisBundle:ValiderFrais:validerfrais.html.twig', array(
            'lesvisiteurs' => $lesVisiteurs,
            'lesmois' => $lesMois,
            'idvisiteur' => $idVisiteurSelectionne,
            'lemois' => $moisSelectionne,
            'ficheExiste' => $ficheExiste,
            'lesfraisforfait' => $lesFraisForfait,
            'lesfraishorsforfait' => $lesFraisHorsForfait,
            'ficheinfo' => $lesInfosFicheFrais,
            'unVehicule' => $unVehicule,
            'visiteurInfo' => $visiteurInfo,
            'message' => $message,
            'messageType' => $messageType
        ));
    }

    public function suivipaiementAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') !== 'comptable') {
            return $this->render('PgGsbFraisBundle:Home:connexion.html.twig', array(
                'message' => 'Accès réservé aux comptables'
            ));
        }

        $pdo = $this->get('pg_gsb_frais.pdo');
        $request = $this->get('request');

        $message = null;
        $messageType = 'info';

        // Action : Mettre en paiement / Rembourser la fiche
        if ($request->getMethod() === 'POST' && $request->request->get('action') === 'mettreEnPaiement') {
            $idVisiteur = $request->request->get('idVisiteur');
            $mois = $request->request->get('mois');
            if ($idVisiteur && $mois) {
                $pdo->majEtatFicheFrais($idVisiteur, $mois, 'RB');
                $message = "La fiche de frais a été marquée comme Remboursée (État passé à RB).";
                $messageType = 'success';
            }
        }

        $moisSelectionne = $request->query->get('lstMois', $request->request->get('lstMois', ''));
        $lesMois = $pdo->getTousLesMoisFiches();
        $lesFiches = $pdo->getLesFichesPourSuiviPaiement($moisSelectionne);

        return $this->render('PgGsbFraisBundle:ValiderFrais:suivipaiement.html.twig', array(
            'lesmois' => $lesMois,
            'lemois' => $moisSelectionne,
            'lesfiches' => $lesFiches,
            'message' => $message,
            'messageType' => $messageType
        ));
    }
}
?>
