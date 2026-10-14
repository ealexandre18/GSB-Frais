<?php
namespace Pg\GsbFraisBundle\services;
require_once __DIR__ . '/../Controller/include/fct.inc.php';
use PDO;
class PdoGsb{   		
	private static $monPdo;
	private static $monPdoGsb=null;
/**
 * Constructeur public, crÃ©e l'instance de PDO qui sera sollicitÃ©e
 * pour toutes les mÃ©thodes de la classe
 */				

        public function __construct($serveur,$bdd,$user,$mdp){
            PdoGsb::$monPdo = new PDO($serveur.';'.$bdd, $user, $mdp);
            PdoGsb::$monPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
	public function _destruct(){
		PdoGsb::$monPdo = null;
	}
/**
 * Fonction statique qui crÃ©e l'unique instance de la classe
 
 * Appel : $instancePdoGsb = PdoGsb::getPdoGsb();
 
 * @return l'unique objet de la classe PdoGsb
 */
	public  static function getPdoGsb(){
		if(PdoGsb::$monPdoGsb==null){
			PdoGsb::$monPdoGsb= new PdoGsb();
		}
		return PdoGsb::$monPdoGsb;  
	}
        
        
/**
 * Retourne les informations d'un visiteur
 
 * @param $login 
 * @param $mdp
 * @return l'id, le nom et le prÃ©nom sous la forme d'un tableau associatif 
*/
	public function getInfosVisiteur($login, $mdp){
		$req = "select visiteur.id as id, visiteur.nom as nom, visiteur.prenom as prenom from visiteur 
		where visiteur.login=:log and visiteur.mdp=:md";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':log', $login);
		$stmt->bindParam(':md', $mdp);
		$stmt->execute();
		$ligne = $stmt->fetch();
		return $ligne;
	}

/**
 * Retourne les informations d'un comptable
 * 
 * @param $login 
 * @param $mdp
 * @return l'id, le nom et le prÃ©nom sous la forme d'un tableau associatif 
 */
	public function getInfosComptable($login, $mdp){
		$req = "select comptable.id as id, comptable.nom as nom, comptable.prenom as prenom from comptable 
		where comptable.login=:log and comptable.mdp=:md";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':log', $login);
		$stmt->bindParam(':md', $mdp);
		$stmt->execute();
		$ligne = $stmt->fetch();
		return $ligne;
	}

/**
 * Retourne sous forme d'un tableau associatif toutes les lignes de frais hors forfait
 * concernÃ©es par les deux arguments
 
 * La boucle foreach ne peut Ãªtre utilisÃ©e ici car on procÃ¨de
 * Ã  une modification de la structure itÃ©rÃ©e - transformation du champ date-
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return tous les champs des lignes de frais hors forfait sous la forme d'un tableau associatif 
*/
	public function getLesFraisHorsForfait($idVisiteur,$mois){
	    $req = "select * from lignefraishorsforfait where lignefraishorsforfait.idvisiteur = :idVisiteur 
		and lignefraishorsforfait.mois = :mois ";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->bindParam(':mois', $mois);
                $stmt->execute();
		$lesLignes = $stmt->fetchAll();
		$nbLignes = count($lesLignes);
		for ($i=0; $i<$nbLignes; $i++){
			$date = $lesLignes[$i]['date'];
			$lesLignes[$i]['date'] =  dateAnglaisVersFrancais($date);
		}
		return $lesLignes; 
	}
	
/**
 * Retourne le nombre de justificatif d'un visiteur pour un mois donnÃ©
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return le nombre entier de justificatifs 
*/
	public function getNbjustificatifs($idVisiteur, $mois){
		$req = "select fichefrais.nbjustificatifs as nb from  fichefrais where fichefrais.idvisiteur =:idVisiteur and fichefrais.mois = :mois";
		$stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->bindParam(':mois', $mois);
                $stmt->execute();
		$laLigne = $stmt->fetch();
		return $laLigne['nb'];
	}
/**
 * Retourne sous forme d'un tableau associatif toutes les lignes de frais au forfait
 * concernÃ©es par les deux arguments
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return l'id, le libelle et la quantitÃ© sous la forme d'un tableau associatif 
*/
	public function getLesFraisForfait($idVisiteur, $mois){
		$req = "select fraisforfait.id as idfrais, fraisforfait.libelle as libelle, fraisforfait.montant as montant, 
		lignefraisforfait.quantite as quantite from lignefraisforfait inner join fraisforfait 
		on fraisforfait.id = lignefraisforfait.idfraisforfait
		where lignefraisforfait.idvisiteur = :idVisiteur and lignefraisforfait.mois= :mois 
		order by lignefraisforfait.idfraisforfait";	
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->bindParam(':mois', $mois);
		$stmt->execute();
		$lesLignes = $stmt->fetchAll();
		return $lesLignes; 
	}
/**
 * Retourne tous les id de la table FraisForfait
 
 * @return un tableau associatif 
*/
	public function getLesIdFrais(){
		$req = "select fraisforfait.id as idfrais from fraisforfait order by fraisforfait.id";
		$stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->execute();
		$lesLignes = $stmt->fetchAll();
		return $lesLignes;
	}
/**
 * Met Ã  jour la table ligneFraisForfait
 
 * Met Ã  jour la table ligneFraisForfait pour un visiteur et
 * un mois donnÃ© en enregistrant les nouveaux montants
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @param $lesFrais tableau associatif de clÃ© idFrais et de valeur la quantitÃ© pour ce frais
 * @return un tableau associatif 
*/
	public function majFraisForfait($idVisiteur, $mois, $lesFrais){
		$lesCles = array_keys($lesFrais);
		foreach($lesCles as $unIdFrais){
			$qte = (int)$lesFrais[$unIdFrais];
			$req = "update lignefraisforfait set quantite = :qte
			where idvisiteur = :idVisiteur and mois = :mois
			and idfraisforfait = :unIdFrais";
			$stmt = PdoGsb::$monPdo->prepare($req);
			$stmt->bindParam(':qte', $qte, \PDO::PARAM_INT);
			$stmt->bindParam(':idVisiteur', $idVisiteur);
			$stmt->bindParam(':mois', $mois);
			$stmt->bindParam(':unIdFrais', $unIdFrais);
			$stmt->execute();
		}
	}

	public function getNbJustificatifsReels($idVisiteur, $mois){
		$req = "select count(*) as nb from lignefraishorsforfait 
				where idvisiteur = :idVisiteur and mois = :mois and justificatif_url is not null and justificatif_url != ''";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->bindParam(':mois', $mois);
		$stmt->execute();
		$res = $stmt->fetch();
		return (int)($res['nb'] ?? 0);
	}
/**
 * met Ã  jour le nombre de justificatifs de la table ficheFrais
 * pour le mois et le visiteur concernÃ©
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
*/
        public function majNbJustificatifs($idVisiteur, $mois, $nbJustificatifs){
                $req = "update fichefrais set nbjustificatifs = :nbJustificatifs 
                where fichefrais.idvisiteur = :idVisiteur and fichefrais.mois = :mois";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->bindParam(':nbJustificatifs', $nbJustificatifs);
                $stmt->execute();
        }
/**
 * Teste si un visiteur possÃ¨de une fiche de frais pour le mois passÃ© en argument
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return vrai ou faux 
*/	
        public function estPremierFraisMois($idVisiteur,$mois)
        {
                $ok = false;
                $req = "select count(*) as nblignesfrais from fichefrais 
                where fichefrais.mois = :mois and fichefrais.idvisiteur = :idVisiteur";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->execute();
                $laLigne = $stmt->fetch();
                if($laLigne['nblignesfrais'] == 0){
                        $ok = true;
                }
                return $ok;
        }
/**
 * Retourne le dernier mois en cours d'un visiteur
 
 * @param $idVisiteur 
 * @return le mois sous la forme aaaamm
*/	
        public function dernierMoisSaisi($idVisiteur){
                $req = "select max(mois) as derniermois from fichefrais where fichefrais.idvisiteur = :idVisiteur";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->execute();
                $laLigne = $stmt->fetch();
                $dernierMois = $laLigne['derniermois'] ?? ($laLigne['dernierMois'] ?? null);
                return $dernierMois;
        }
	
/**
 * CrÃ©e une nouvelle fiche de frais et les lignes de frais au forfait pour un visiteur et un mois donnÃ©s
 
 * rÃ©cupÃ¨re le dernier mois en cours de traitement, met Ã  'CL' son champs idEtat, crÃ©e une nouvelle fiche de frais
 * avec un idEtat Ã  'CR' et crÃ©e les lignes de frais forfait de quantitÃ©s nulles 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
*/
        public function creeNouvellesLignesFrais($idVisiteur,$mois){
                $dernierMois = $this->dernierMoisSaisi($idVisiteur);
                if ($dernierMois) {
                    $laDerniereFiche = $this->getLesInfosFicheFrais($idVisiteur,$dernierMois);
                    if ($laDerniereFiche && ($laDerniereFiche['idetat'] ?? ($laDerniereFiche['idEtat'] ?? '')) == 'CR') {
                        $this->majEtatFicheFrais($idVisiteur, $dernierMois,'CL');
                    }
                }
                $dateModif = date('Y-m-d');
                $req = "insert into fichefrais(idvisiteur,mois,nbJustificatifs,montantValide,dateModif,idEtat) 
                values(:idVisiteur,:mois,0,0,:dateModif,'CR')";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->bindParam(':dateModif', $dateModif);
                $stmt->execute();
                $lesIdFrais = $this->getLesIdFrais();
                foreach($lesIdFrais as $uneLigneIdFrais){
                        $unIdFrais = $uneLigneIdFrais['idfrais'];
                        $req = "insert into lignefraisforfait(idvisiteur,mois,idFraisForfait,quantite) 
                        values(:idVisiteur,:mois,:unIdFrais,0)";
                        $stmt = PdoGsb::$monPdo->prepare($req);
                        $stmt->bindParam(':idVisiteur', $idVisiteur);
                        $stmt->bindParam(':mois', $mois);
                        $stmt->bindParam(':unIdFrais', $unIdFrais);
                        $stmt->execute();
                 }
        }
/**
 * CrÃ©e un nouveau frais hors forfait pour un visiteur un mois donnÃ©
 * Ã  partir des informations fournies en paramÃ¨tre
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @param $libelle : le libelle du frais
 * @param $date : la date du frais au format franÃ§ais jj//mm/aaaa
 * @param $montant : le montant
*/
        public function creeNouveauFraisHorsForfait($idVisiteur,$mois,$libelle,$date,$montant,$justificatifUrl=null){
                if (strpos($date, '/') !== false && function_exists('\dateFrancaisVersAnglais')) {
                    $dateFr = \dateFrancaisVersAnglais($date);
                } else if (strpos($date, '/') !== false) {
                    @list($jour,$m,$annee) = explode('/', $date);
                    $dateFr = date('Y-m-d', mktime(0,0,0, (int)$m, (int)$jour, (int)$annee));
                } else {
                    $dateFr = $date;
                }
                $req = "insert into lignefraishorsforfait(idvisiteur,mois,libelle,date,montant,justificatif_url) 
                values(:idVisiteur,:mois,:libelle,:dateFr,:montant,:justificatifUrl)";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->bindParam(':libelle', $libelle);
                $stmt->bindParam(':dateFr', $dateFr);
                $stmt->bindParam(':montant', $montant);
                $stmt->bindParam(':justificatifUrl', $justificatifUrl);
                $stmt->execute();
        }
/**
 * Supprime le frais hors forfait dont l'id est passÃ© en argument
 
 * @param $idFrais 
*/
        public function supprimerFraisHorsForfait($idFrais){
                $req = "delete from lignefraishorsforfait where lignefraishorsforfait.id =:idFrais ";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idFrais', $idFrais);
                $stmt->execute();
        }
/**
 * Met Ã  jour le justificatif d'un frais hors forfait existant
 * 
 * @param $idFrais
 * @param $justificatifUrl
 */
        public function majJustificatifFraisHorsForfait($idFrais, $justificatifUrl){
                $req = "update lignefraishorsforfait set justificatif_url = :justificatifUrl where id = :idFrais";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':justificatifUrl', $justificatifUrl);
                $stmt->bindParam(':idFrais', $idFrais);
                $stmt->execute();
        }
/**
 * Retourne les mois pour lesquel un visiteur a une fiche de frais
 
 * @param $idVisiteur 
 * @return un tableau associatif de clÃ© un mois -aaaamm- et de valeurs l'annÃ©e et le mois correspondant 
*/
        public function getLesMoisDisponibles($idVisiteur){
                $req = "select fichefrais.mois as mois from  fichefrais where fichefrais.idvisiteur =:idVisiteur 
                order by fichefrais.mois desc ";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->execute();
                $laLigne = $stmt->fetch();
                $lesMois =array();
                while($laLigne != null)	{
                        $mois = $laLigne['mois'];
                        $numAnnee =substr( $mois,0,4);
                        $numMois =substr( $mois,4,2);
                        $lesMois["$mois"]=array(
                        "mois"=>"$mois",
                        "numAnnee"  => "$numAnnee",
                        "numMois"  => "$numMois"
                        );
                        $laLigne = $stmt->fetch(); 		
                }
                return $lesMois;
        }
/**
 * Retourne les informations d'une fiche de frais d'un visiteur pour un mois donnÃ©
 
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 * @return un tableau avec des champs de jointure entre une fiche de frais et la ligne d'Ã©tat 
*/	
        public function getLesInfosFicheFrais($idVisiteur,$mois){
                $req = "select fichefrais.idetat as \"idEtat\", fichefrais.datemodif as \"dateModif\", 
                        fichefrais.nbjustificatifs as \"nbJustificatifs\", fichefrais.montantvalide as \"montantValide\", 
                        etat.libelle as \"libEtat\",
                        fichefrais.idetat, fichefrais.datemodif, fichefrais.nbjustificatifs, 
                        fichefrais.montantvalide, etat.libelle as libetat
                        from fichefrais inner join etat on fichefrais.idetat = etat.id 
                        where fichefrais.idvisiteur = :idVisiteur and fichefrais.mois = :mois";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->execute();
                $laLigne = $stmt->fetch();
                return $laLigne;
        }
/**
 * Modifie l'Ã©tat et la date de modification d'une fiche de frais
 
 * Modifie le champ idEtat et met la date de modif Ã  aujourd'hui
 * @param $idVisiteur 
 * @param $mois sous la forme aaaamm
 */
 
        public function majEtatFicheFrais($idVisiteur,$mois,$etat){
                $dateModif = date('Y-m-d');
                $req = "update fichefrais set idEtat = :etat, dateModif = :dateModif 
                where fichefrais.idvisiteur = :idVisiteur and fichefrais.mois = :mois";
                $stmt = PdoGsb::$monPdo->prepare($req);
                $stmt->bindParam(':idVisiteur', $idVisiteur);
                $stmt->bindParam(':mois', $mois);
                $stmt->bindParam(':etat', $etat);
                $stmt->bindParam(':dateModif', $dateModif);
                $stmt->execute();

        }
/**
 * Retourne toutes les fiches de frais dont l'Ã©tat est Ã  validÃ©
 * @return type
 */
        public function getLesFichesValidees()
        {
            $req = "select * from fichefrais
                    inner join etat on fichefrais.idetat = etat.id
                    inner join visiteur on visiteur.id = fichefrais.idVisiteur
                    where idetat = 'VA'";	
            $stmt = PdoGsb::$monPdo->prepare($req);
            $stmt->execute();
            $lesLignes = $stmt->fetchAll();
            return $lesLignes; 
        }
/**
 * VÃ©rifie si un frais existe pour un visiteur et un mois donnÃ©
 * @param type $idVisiteur
 * @param type $mois
 * @param type $idFrais
 * @return 1 ou 0
 */
        public function estValideSuppressionFrais($idVisiteur,$mois,$idFrais){
            $req = "select count(*) as nb from lignefraishorsforfait 
            where lignefraishorsforfait.id=:idfrais and lignefraishorsforfait.mois=:mois
            and lignefraishorsforfait.idvisiteur=:idvisiteur";
            $stmt = PdoGsb::$monPdo->prepare($req);
            $stmt->bindParam(':idfrais', $idFrais);
            $stmt->bindParam(':mois', $mois);
            $stmt->bindParam(':idvisiteur', $idVisiteur);
            $stmt->execute();
            $ligne = $stmt->fetch();
            return $ligne['nb'];

        }
/**
 * Retourne le vÃ©hicule et le barÃ¨me kilomÃ©trique d'un visiteur
 * 
 * @param $idVisiteur 
 * @return tableau associatif contenant carburant, puissance, montant_km
 */
	public function getVehiculeVisiteur($idVisiteur){
		$req = "select vehicule.carburant, vehicule.puissance, vehicule.montant_km 
				from visiteur inner join vehicule on visiteur.id_vehicule = vehicule.id 
				where visiteur.id = :idVisiteur";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->execute();
		$leVehicule = $stmt->fetch();
		return $leVehicule;
	}

/**
 * Retourne les informations complÃ¨tes d'un visiteur par son id
 * @param $idVisiteur
 * @return array
 */
	public function getInfosVisiteurParId($idVisiteur){
		$req = "select visiteur.id, visiteur.nom, visiteur.prenom, visiteur.adresse, visiteur.cp, visiteur.ville, visiteur.id_vehicule 
				from visiteur where visiteur.id = :idVisiteur";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->execute();
		return $stmt->fetch();
	}

/**
 * Retourne toutes les fiches clÃ´turÃ©es, validÃ©es ou remboursÃ©es d'un mois donnÃ©
 * @param $mois
 * @return array
 */
	public function getLesFichesClotureesMois($mois){
		$req = "select fichefrais.idvisiteur, fichefrais.mois, fichefrais.nbjustificatifs, 
					   fichefrais.montantvalide, fichefrais.datemodif, fichefrais.idetat,
					   etat.libelle as libetat,
					   visiteur.nom, visiteur.prenom 
				from fichefrais 
				inner join visiteur on fichefrais.idvisiteur = visiteur.id
				inner join etat on fichefrais.idetat = etat.id
				where fichefrais.mois = :mois and (fichefrais.idetat = 'CL' or fichefrais.idetat = 'VA' or fichefrais.idetat = 'RB')
				order by visiteur.nom, visiteur.prenom";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':mois', $mois);
		$stmt->execute();
		return $stmt->fetchAll();
	}

/**
 * Retourne la liste de tous les mois disponibles dans la base de donnÃ©es
 * @return array
 */
	public function getTousLesMoisFiches(){
		$req = "select distinct mois from fichefrais order by mois desc";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->execute();
		$lignes = $stmt->fetchAll();
		$lesMois = array();
		foreach ($lignes as $uneLigne) {
			$cle = $uneLigne['mois'];
			$lesMois[$cle] = array(
				'mois' => $cle,
				'numAnnee' => substr($cle, 0, 4),
				'numMois' => substr($cle, 4, 2)
			);
		}
		return $lesMois;
	}

/**
 * Retourne les fiches pour le suivi de paiement (Ã©tat VA ou RB)
 * @param $mois
 * @return array
 */
	public function getLesFichesPourSuiviPaiement($mois = null){
		$req = "select fichefrais.idvisiteur, fichefrais.mois, fichefrais.nbjustificatifs, 
					   fichefrais.montantvalide, fichefrais.datemodif, fichefrais.idetat,
					   etat.libelle as libetat, visiteur.nom, visiteur.prenom 
				from fichefrais 
				inner join visiteur on fichefrais.idvisiteur = visiteur.id
				inner join etat on fichefrais.idetat = etat.id
				where fichefrais.idetat in ('VA', 'RB') ";
		if (!empty($mois)) {
			$req .= " and fichefrais.mois = :mois ";
		}
		$req .= " order by fichefrais.mois desc, visiteur.nom asc";
		$stmt = PdoGsb::$monPdo->prepare($req);
		if (!empty($mois)) {
			$stmt->bindParam(':mois', $mois);
		}
		$stmt->execute();
		return $stmt->fetchAll();
	}

/**
 * Retourne la liste de tous les visiteurs mÃ©dicaux
 * @return array
 */
	public function getTousLesVisiteurs(){
		$req = "select visiteur.id, visiteur.nom, visiteur.prenom, visiteur.login, visiteur.cp, visiteur.ville, visiteur.dateembauche, coalesce(visiteur.statut, 'Actif') as statut from visiteur order by visiteur.nom, visiteur.prenom";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->execute();
		return $stmt->fetchAll();
	}

/**
 * Refuse un frais hors forfait en prÃ©fixant son libellÃ© par 'REFUSE : '
 * @param $idFrais
 */
	public function refuserFraisHorsForfait($idFrais){
		$req = "select libelle from lignefraishorsforfait where id = :idFrais";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':idFrais', $idFrais);
		$stmt->execute();
		$ligne = $stmt->fetch();
		if ($ligne) {
			$libelle = $ligne['libelle'];
			if (strpos($libelle, 'REFUSE : ') !== 0) {
				$nouveauLibelle = 'REFUSE : ' . $libelle;
				$reqMaj = "update lignefraishorsforfait set libelle = :libelle where id = :idFrais";
				$stmtMaj = PdoGsb::$monPdo->prepare($reqMaj);
				$stmtMaj->bindParam(':libelle', $nouveauLibelle);
				$stmtMaj->bindParam(':idFrais', $idFrais);
				$stmtMaj->execute();
			}
		}
	}

/**
 * Reporte un frais hors forfait sur la fiche du mois suivant
 * @param $idFrais
 * @param $idVisiteur
 * @param $mois
 */
	public function reporterFraisHorsForfait($idFrais, $idVisiteur, $mois){
		$annee = (int)substr($mois, 0, 4);
		$numMois = (int)substr($mois, 4, 2);
		$numMoisSuivant = $numMois + 1;
		$anneeSuivante = $annee;
		if ($numMoisSuivant > 12) {
			$numMoisSuivant = 1;
			$anneeSuivante++;
		}
		$moisSuivant = sprintf('%04d%02d', $anneeSuivante, $numMoisSuivant);
		
		if ($this->estPremierFraisMois($idVisiteur, $moisSuivant)) {
			$this->creeNouvellesLignesFrais($idVisiteur, $moisSuivant);
		}
		
		$req = "update lignefraishorsforfait set mois = :moisSuivant where id = :idFrais";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':moisSuivant', $moisSuivant);
		$stmt->bindParam(':idFrais', $idFrais);
		$stmt->execute();
	}

/**
 * Valide une fiche de frais : calcule le montant total et passe l'Ã©tat Ã  'VA'
 * @param $idVisiteur
 * @param $mois
 * @param $nbJustificatifs
 */
	public function validerFicheFrais($idVisiteur, $mois, $nbJustificatifs){
		$lesFraisForfait = $this->getLesFraisForfait($idVisiteur, $mois);
		$vehicule = $this->getVehiculeVisiteur($idVisiteur);
		$totalForfait = 0;
		foreach ($lesFraisForfait as $unFrais) {
			$qte = (float)($unFrais['quantite'] ?? 0);
			$montantUnitaire = (float)($unFrais['montant'] ?? 0);
			if ($unFrais['idfrais'] === 'KM' && !empty($vehicule['montant_km'])) {
				$montantUnitaire = (float)$vehicule['montant_km'];
			}
			$totalForfait += $qte * $montantUnitaire;
		}
		
		$lesFraisHF = $this->getLesFraisHorsForfait($idVisiteur, $mois);
		$totalHF = 0;
		foreach ($lesFraisHF as $unFraisHF) {
			if (strpos($unFraisHF['libelle'], 'REFUSE : ') !== 0) {
				$totalHF += (float)($unFraisHF['montant'] ?? 0);
			}
		}
		
		$totalValide = $totalForfait + $totalHF;
		
		$dateModif = date('Y-m-d');
		$req = "update fichefrais set idetat = 'VA', montantvalide = :totalValide, nbjustificatifs = :nbJustificatifs, datemodif = :dateModif 
				where idvisiteur = :idVisiteur and mois = :mois";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':totalValide', $totalValide);
		$stmt->bindParam(':nbJustificatifs', $nbJustificatifs);
		$stmt->bindParam(':dateModif', $dateModif);
		$stmt->bindParam(':idVisiteur', $idVisiteur);
		$stmt->bindParam(':mois', $mois);
		$stmt->execute();
	}


/**
 * Retourne la liste de tous les agents comptables
 * @return array
 */
	public function getTousLesComptables(){
		$req = "select id, nom, prenom, login, coalesce(statut, 'HabilitÃ©') as statut from comptable order by nom, prenom";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->execute();
		return $stmt->fetchAll();
	}

/**
 * Retourne les informations d'un administrateur
 */
	public function getInfosAdmin($login, $mdp){
		$req = "select id, nom, prenom from administrateur where login = :log and mdp = :md";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':log', $login);
		$stmt->bindParam(':md', $mdp);
		$stmt->execute();
		return $stmt->fetch();
	}

/**
 * CrÃ©e un nouveau visiteur mÃ©dical
 */
	public function creerVisiteur($id, $nom, $prenom, $login, $mdp, $adresse, $cp, $ville, $dateEmbauche, $idVehicule){
		$req = "insert into visiteur (id, nom, prenom, login, mdp, adresse, cp, ville, dateembauche, id_vehicule) 
				values (:id, :nom, :prenom, :login, :mdp, :adresse, :cp, :ville, :dateEmbauche, :idVehicule)";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':nom', $nom);
		$stmt->bindParam(':prenom', $prenom);
		$stmt->bindParam(':login', $login);
		$stmt->bindParam(':mdp', $mdp);
		$stmt->bindParam(':adresse', $adresse);
		$stmt->bindParam(':cp', $cp);
		$stmt->bindParam(':ville', $ville);
		$stmt->bindParam(':dateEmbauche', $dateEmbauche);
		$stmt->bindParam(':idVehicule', $idVehicule);
		$stmt->execute();
	}

/**
 * CrÃ©e un nouvel agent comptable
 */
	public function creerComptable($id, $nom, $prenom, $login, $mdp){
		$req = "insert into comptable (id, nom, prenom, login, mdp) 
				values (:id, :nom, :prenom, :login, :mdp)";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':nom', $nom);
		$stmt->bindParam(':prenom', $prenom);
		$stmt->bindParam(':login', $login);
		$stmt->bindParam(':mdp', $mdp);
		$stmt->execute();
	}

/**
 * Retourne les informations complÃ¨tes d'un visiteur par son id
 */
	public function getVisiteurParId($id){
		$req = "select id, nom, prenom, login, adresse, cp, ville, dateembauche, id_vehicule, coalesce(statut, 'Actif') as statut 
				from visiteur where id = :id";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		return $stmt->fetch();
	}

/**
 * Retourne les informations complÃ¨tes d'un comptable par son id
 */
	public function getComptableParId($id){
		$req = "select id, nom, prenom, login, coalesce(statut, 'HabilitÃ©') as statut 
				from comptable where id = :id";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		return $stmt->fetch();
	}

/**
 * Modifie les coordonnÃ©es d'un visiteur mÃ©dical
 */
	public function modifierVisiteur($id, $nom, $prenom, $login, $adresse, $cp, $ville, $idVehicule){
		$req = "update visiteur set nom = :nom, prenom = :prenom, login = :login, adresse = :adresse, 
				cp = :cp, ville = :ville, id_vehicule = :idVehicule where id = :id";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':nom', $nom);
		$stmt->bindParam(':prenom', $prenom);
		$stmt->bindParam(':login', $login);
		$stmt->bindParam(':adresse', $adresse);
		$stmt->bindParam(':cp', $cp);
		$stmt->bindParam(':ville', $ville);
		$stmt->bindParam(':idVehicule', $idVehicule);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
	}

/**
 * Modifie les coordonnÃ©es d'un comptable
 */
	public function modifierComptable($id, $nom, $prenom, $login){
		$req = "update comptable set nom = :nom, prenom = :prenom, login = :login where id = :id";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':nom', $nom);
		$stmt->bindParam(':prenom', $prenom);
		$stmt->bindParam(':login', $login);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
	}

/**
 * RÃ©initialise le mot de passe d'un utilisateur
 */
	public function reinitialiserMdp($type, $id, $nouveauMdp = 'secret'){
		$table = ($type === 'comptable') ? 'comptable' : 'visiteur';
		$req = "update $table set mdp = :mdp where id = :id";
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':mdp', $nouveauMdp);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
	}

/**
 * Active / DÃ©sactive un compte utilisateur ou RÃ©voque / Habilite un comptable
 */
	public function toggleStatutUtilisateur($type, $id){
		if ($type === 'comptable') {
			$cur = $this->getComptableParId($id);
			$nouveauStatut = (($cur['statut'] ?? '') === 'HabilitÃ©') ? 'RÃ©voquÃ©' : 'HabilitÃ©';
			$req = "update comptable set statut = :st where id = :id";
		} else {
			$cur = $this->getVisiteurParId($id);
			$nouveauStatut = (($cur['statut'] ?? '') === 'Actif') ? 'DÃ©sactivÃ©' : 'Actif';
			$req = "update visiteur set statut = :st where id = :id";
		}
		$stmt = PdoGsb::$monPdo->prepare($req);
		$stmt->bindParam(':st', $nouveauStatut);
		$stmt->bindParam(':id', $id);
		$stmt->execute();
		return $nouveauStatut;
	}
}
?>
