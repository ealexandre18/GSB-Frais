<?php
namespace Pg\GsbFraisBundle\Controller;
require_once("include/fct.inc.php");
//require_once ("include/class.pdogsb.inc.php");
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
//use PdoGsb;
class ListeFraisController extends Controller
{
    public function indexAction()
    {
        $session= $this->get('request')->getSession();
        if ($session->get('role') === 'comptable') {
            return $this->render('PgGsbFraisBundle:accueil.html.twig');
        }
        $idVisiteur =  $session->get('id');
        $pdo = $this->get('pg_gsb_frais.pdo');
        $lesMois=$pdo->getLesMoisDisponibles($idVisiteur);
        if($this->get('request')->getMethod() == 'GET'){
            $lesCles = array_keys( $lesMois );
            $moisASelectionner = !empty($lesCles) ? $lesCles[0] : null;
            return $this->render('PgGsbFraisBundle:ListeFrais:listemois.html.twig',
                array('lesmois'=>$lesMois,'lemois'=>$moisASelectionner));
        }
        else{
            $request = $this->get('request');
            $leMois =  $request->request->get('lstMois');
            $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur,$leMois);
            $lesFraisForfait= $pdo->getLesFraisForfait($idVisiteur,$leMois);
            $lesInfosFicheFrais = $pdo->getLesInfosFicheFrais($idVisiteur,$leMois);
            $numAnnee = substr( $leMois,0,4);
            $numMois = substr( $leMois,4,2);
            $libEtat = $lesInfosFicheFrais['libetat'] ?? ($lesInfosFicheFrais['libEtat'] ?? 'Non renseigné');
            $montantValide = $lesInfosFicheFrais['montantvalide'] ?? ($lesInfosFicheFrais['montantValide'] ?? '0.00');
            $nbJustificatifs = (int)($lesInfosFicheFrais['nbjustificatifs'] ?? ($lesInfosFicheFrais['nbJustificatifs'] ?? 0));
            if ($nbJustificatifs === 0) {
                $nbJustificatifs = $pdo->getNbJustificatifsReels($idVisiteur, $leMois);
            }
            $dateModif =  $lesInfosFicheFrais['datemodif'] ?? ($lesInfosFicheFrais['dateModif'] ?? date('Y-m-d'));
            $dateModif =  dateAnglaisVersFrancais($dateModif);
            return $this->render('PgGsbFraisBundle:ListeFrais:listetouslesfrais.html.twig',
                array('lesmois'=>$lesMois,'lesfraisforfait'=>$lesFraisForfait,'lesfraishorsforfait'=>$lesFraisHorsForfait,
                    'lemois'=>$leMois,'numannee'=>$numAnnee,'nummois'=> $numMois,'libetat'=>$libEtat,
                        'montantvalide'=>$montantValide,'nbjustificatifs'=>$nbJustificatifs,'datemodif'=>$dateModif));
            
        }
    }
        
    public function genererpdfAction()
    {
        $session = $this->get('request')->getSession();
        $request = $this->get('request');
        
        $idVisiteur = $request->query->get('idVisiteur') ?? $session->get('id');
        $mois = $request->query->get('mois');
        
        if (empty($mois)) {
            $mois = getMois(date("d/m/Y"));
        }
        
        $pdo = $this->get('pg_gsb_frais.pdo');
        $visiteur = $pdo->getInfosVisiteurParId($idVisiteur);
        if (!$visiteur) {
            $visiteur = array(
                'id' => $idVisiteur,
                'nom' => $session->get('nom', ''),
                'prenom' => $session->get('prenom', '')
            );
        }
        
        $lesFraisForfait = $pdo->getLesFraisForfait($idVisiteur, $mois);
        $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVisiteur, $mois);
        $vehicule = $pdo->getVehiculeVisiteur($idVisiteur);
        
        require_once __DIR__ . '/../services/PdfGsb.php';
        $pdf = new \Pg\GsbFraisBundle\services\PdfGsb();
        $pdf->ajouterFicheRemboursement($visiteur, $mois, $lesFraisForfait, $lesFraisHorsForfait, $vehicule);
        
        $filename = 'Fiche_Remboursement_' . $idVisiteur . '_' . $mois . '.pdf';
        
        $pdfContent = $pdf->Output('S');
        $response = new \Symfony\Component\HttpFoundation\Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="' . $filename . '"');
        return $response;
    }

    public function genererpdfmoisAction()
    {
        $session = $this->get('request')->getSession();
        if ($session->get('role') !== 'comptable') {
            return $this->render('PgGsbFraisBundle:Home:connexion.html.twig', array(
                'message' => 'Accès réservé aux comptables'
            ));
        }

        $request = $this->get('request');
        $pdo = $this->get('pg_gsb_frais.pdo');
        $mois = $request->query->get('mois', $request->request->get('mois'));
        $telecharger = $request->query->get('telecharger', $request->request->get('telecharger'));
        
        $lesMois = $pdo->getTousLesMoisFiches();
        if (empty($mois)) {
            $lesClesMois = array_keys($lesMois);
            $mois = !empty($lesClesMois) ? $lesClesMois[0] : getMois(date("d/m/Y"));
        }
        
        $fiches = $pdo->getLesFichesClotureesMois($mois);

        // Si l'utilisateur clique sur Télécharger le PDF global
        if ($telecharger == '1' || $telecharger === 'true') {
            if (empty($fiches)) {
                return $this->render('PgGsbFraisBundle:ValiderFrais:pdffichescloturees.html.twig', array(
                    'lesmois' => $lesMois,
                    'lemois' => $mois,
                    'fiches' => array(),
                    'message' => "Aucune fiche clôturée, validée ou remboursée trouvée pour le mois " . substr($mois,4,2) . "/" . substr($mois,0,4) . ".",
                    'messageType' => 'warning'
                ));
            }
            
            require_once __DIR__ . '/../services/PdfGsb.php';
            $pdf = new \Pg\GsbFraisBundle\services\PdfGsb();
            
            foreach ($fiches as $uneFiche) {
                $idVis = $uneFiche['idvisiteur'];
                $visiteur = $pdo->getInfosVisiteurParId($idVis);
                if (!$visiteur) {
                    $visiteur = array(
                        'id' => $idVis,
                        'nom' => $uneFiche['nom'] ?? '',
                        'prenom' => $uneFiche['prenom'] ?? ''
                    );
                }
                $lesFraisForfait = $pdo->getLesFraisForfait($idVis, $mois);
                $lesFraisHorsForfait = $pdo->getLesFraisHorsForfait($idVis, $mois);
                $vehicule = $pdo->getVehiculeVisiteur($idVis);
                
                $pdf->ajouterFicheRemboursement($visiteur, $mois, $lesFraisForfait, $lesFraisHorsForfait, $vehicule);
            }
            
            $filename = 'Toutes_Fiches_' . $mois . '.pdf';
            $pdfContent = $pdf->Output('S');
            $response = new \Symfony\Component\HttpFoundation\Response($pdfContent);
            $response->headers->set('Content-Type', 'application/pdf');
            $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
            return $response;
        }

        // Affichage de la page avec sélecteur de mois
        return $this->render('PgGsbFraisBundle:ValiderFrais:pdffichescloturees.html.twig', array(
            'lesmois' => $lesMois,
            'lemois' => $mois,
            'fiches' => $fiches,
            'message' => empty($fiches) ? "Aucune fiche clôturée ou validée trouvée pour ce mois." : null,
            'messageType' => 'info'
        ));
    }
}
?>
