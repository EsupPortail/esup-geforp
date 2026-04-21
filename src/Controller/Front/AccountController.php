<?php
/**
 * Created by PhpStorm.
 */

namespace App\Controller\Front;


use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Title;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\Type\ProfileType;
use http\Env\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Back\Trainee;
use App\Entity\Back\SupannCodeEntite;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route(path: '/account')]
final class AccountController extends AbstractController
{
    private LoggerInterface $logger;
    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry, LoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public function index(): \Symfony\Component\HttpFoundation\Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
        return $this->render('Front/Account/profile/profile.html.twig');
    }


    #[Route(path: '/', name: 'front.account')]
    public function account(ManagerRegistry $managerRegistry): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $arTrainee = [];
        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');

        // Récupération des attributs Shibboleth pour mise à jour du profil
        $shibbolethAttributes = $this->getUser()->getCredentials();

        $userEmail = $this->getUser()->getCredentials()['mail'];
        // on utilise l'eppn comme persistent-id
        $userPersitentId = $this->getUser()->getCredentials()['eppn'];
        $flagUpdatePersistentId = 0;

        // On teste l'eppn
        if (isset($userPersitentId)) {
            $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(["shibbolethpersistentid" => $userPersitentId]);
            if ($arTrainee !== null) {
                // Si on a un stagiaire en base, on ne fait rien et on mettra à jour dans la suite du code
            } elseif (isset($userEmail)) {
                // si on ne trouve pas de stagiaire en base avec eppn, on regarde s'il y en a un avec le mail
                $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(["email" =>$userEmail]);
                if (isset($arTrainee)) {
                    // Il y a bien un stagiaire en base, mais il n'a pas été retrouvé avec l'eppn -> on met à jour le persistent id
                    $flagUpdatePersistentId = 1;
                }
            }
        } elseif (isset($userEmail)) {
            $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findOneBy($userEmail);
        }

        if (isset($arTrainee)) {
            $trainee = $arTrainee;

            // Si identification avec le mail, on met à jour l'eppn
            if ($flagUpdatePersistentId)
                $trainee->setShibbolethpersistentid($shibbolethAttributes['eppn']);

            // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
            if ($shibbolethAttributes['supannCivilite']=='')
                $shibbolethAttributes['supannCivilite'] = 'M.';

            $trainee->setTitle($managerRegistry->getRepository(\App\Entity\Term\Title::class)->findOneBy(
                ['name' => $shibbolethAttributes['supannCivilite']]
            ));

            // Gestion du sn et du givenname multivalués
            $sn = explode(";", $shibbolethAttributes['sn']);
            $givenName = explode(";", $shibbolethAttributes['givenName']);
            $trainee->setLastName($sn[0]);
            $trainee->setFirstName($givenName[0]);
            $trainee->setEmail($shibbolethAttributes['mail']);
            $datenaiss = str_replace("-", "", (string) $shibbolethAttributes['supannOIDCDateDeNaissance']);
            $trainee->setBirthDate($datenaiss);
            // Mise en forme adresse au cas où il y en a une
            if ($adresseFromLdap && ($shibbolethAttributes['postalAddress'] != "")) {
                $address = $shibbolethAttributes['postalAddress'];
                // Recupération du code postal
                preg_match('#\$\d{5}#', (string) $address, $result, PREG_OFFSET_CAPTURE, 3);
                if (isset($result[0][0]) && isset($result[0][1])) {

                    $codepostal = substr($result[0][0], 1);
                    // Récupération position du dernier $ dans la chaine
                    $posLast = strripos((string) $address, "$");
                    // Adresse = début de la chaîne jusqu'au code postal
                    $addressPro = substr((string) $address, 0, $result[0][1]);
                    // On retire les '$' restants dans l'adresse
                    $addressPro = str_replace("$", " / ", $addressPro);
                    if ($posLast == $result[0][1]) {
                        // Si il n'y a pas de pays renseigné
                        $city = substr((string) $address, $result[0][1] + 6);
                    } else {
                        // Si il y a un pays, on recupère seulement la partie ville
                        $city = substr((string) $address, $result[0][1] + 6, $posLast - $result[0][1] - 6);
                    }

                    $trainee->setAddress($addressPro);
                    $trainee->setCity($city);
                    $trainee->setZip($codepostal);
                }
            }

            $trainee->setPhoneNumber($shibbolethAttributes['telephoneNumber']);
			$shibbolethAttributes['primary-affiliation'] = strtolower($shibbolethAttributes['primary-affiliation']);
            if ($shibbolethAttributes['primary-affiliation'] == "staff") {
                // Transformation de l'attribut 'staff' en 'employee'
                $shibbolethAttributes['primary-affiliation'] = "employee";
            }

            $primary_affiliationRepo = $managerRegistry->getRepository(\App\Entity\Term\Publictype::class);
            // Recherche avec la valeur convertie

           $primary_affiliation = $primary_affiliationRepo->findOneBy(['machinename' => $shibbolethAttributes['primary-affiliation']]);

            if ($primary_affiliation != null) {
                // cas general
                $trainee->setPublictype($primary_affiliation);
            } elseif ($shibbolethAttributes['primary-affiliation'] == 'student') {
                // cas des etudiants doctorants
                $flagDoc = 0;
                if ($shibbolethAttributes['supannEtuCursusAnnee'] != "") {
                    // Test si doctorant sur supannEtuCursusAnnee
                    $tabCursus = $shibbolethAttributes['supannEtuCursusAnnee'];
                    if (is_array($tabCursus)) {
                        foreach ($tabCursus as $tabCursu) {
                            if (str_contains((string) $tabCursu, '{SUPANN}D')) {
                                // c'est un doctorant
                                $flagDoc = 1;
                                $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                                    ['name' => 'enseignant']
                                ));
                                break;
                            }
                        }
                    } elseif (str_contains((string) $tabCursus, '{SUPANN}D')) {
                        // c'est un doctorant
                        $flagDoc = 1;
                        $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                            ['name' => 'enseignant']
                        ));
                    }
                }
                // si pas trouvé sur supannEtuCursusAnnee, test sur unscoped-affiliation
                if ($flagDoc == 0) {
                    // si etudiant, on regarde aussi edupersonaffiliation pour détecter les doctorants
                    $affiliation = explode(';', (string) $shibbolethAttributes['unscoped-affiliation']);
                    // Recup des types de public possibles
                    $allPublictypes = $managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findAll();
                    foreach ($affiliation as $aff) {
                        foreach ($allPublictypes as $allPublictype) {
                            if ($aff == $allPublictype->getMachinename()) {
                                $flagDoc = 1;
                                $trainee->setPublictype($allPublictype);
                                break 2;
                            }
                        }
                    }
                }
                if ($flagDoc == 0) {
                    // Etudiant 'simple', pas doctorant -> n'a pas accès à l'application
                    $this->addFlash('error', 'Vous ne pouvez pas vous inscrire sur Geforp. La plate-forme n\'est pas accessible aux étudiants.');
                    return $this->redirectToRoute('front.public.index');
                }
            } else {
                $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                    ['machinename' => 'other']
                ));
            }

            // Etablissement
            $flagEtab = 0;
            $listeEtab = $managerRegistry->getRepository(\App\Entity\Back\Institution::class)->findAll();
            $eppn = $shibbolethAttributes['eppn'];
            if (stripos((string) $eppn , "@")>0) {
                // recup domaine dans l'eppn
                $domaine = substr((string) $eppn, stripos((string) $eppn, "@") + 1);
                foreach ($listeEtab as $etab) {
                    $domaines = $etab->getDomains();
                    foreach ($domaines as $dom) {
                        // test domaine de l'eppn et domaines renseignés pour les établissements définis en BDD
                        if (strtolower((string) $dom->getName()) === strtolower($domaine)) {
                            $trainee->setInstitution($etab);
                            $flagEtab = 1;
                            break 2;
                        }
                    }
                }
            }

            if ($flagEtab !== 1) {
                // Pb pas d'etablissement defini -> message d'erreur pour le stagiaire
                $this->addFlash('error', 'Vous ne pouvez pas vous inscrire sur Geforp. Votre établissement n\'a pas accès à la plate-forme.');
                return $this->redirectToRoute('front.public.index');
            }

            // Attributs AMU
            if ($trainee->getInstitution()->getName() == "AMU") {
                $trainee->setService($shibbolethAttributes['amuAffectationLib']);
                $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
                //$trainee->setBap($shibbolethAttributes['amuBap']);
                $trainee->setCampus($shibbolethAttributes['amuCampus']);
                $bap = "";
                $activites = explode(";", (string) $shibbolethAttributes['supannActivite']);
                foreach($activites as $activite) {
                    $pos = stripos($activite, "{BAP}");
                    if ($pos !== false) {
                        $bap = ltrim($activite, "{BAP}");
                        // si {BAP} est trouvé, on arrête
                        break;
                    }
                }

                $trainee->setBap($bap);
                $spCorps = explode(";", (string) $shibbolethAttributes['supannEmpCorps']);
                foreach($spCorps as $spCorp) {
                    $pos = stripos($spCorp, "{NCORPS}");
                    if ($pos !== false) {
                        $corps = ltrim($spCorp, "{NCORPS}");
                        if (ctype_digit($corps))
                            $corps = (int)$corps;

                        $n_corps = $this->managerRegistry->getRepository(\App\Entity\Back\Corps::class)->findOneBy(
                            ['corps' => $corps]
                        );
                        if ($n_corps != null) {
                            $trainee->setCorps($n_corps->getLibelleLong());
                            $trainee->setCategory($n_corps->getCategory());
                        }

                        // si {NCORPS} est trouvé, on arrête
                        break;
                    }
                }
            } else {
                $libAff = $this->getParameter('lib_affectation');
                // si le libellé pour l'affection principale n'est pas précisé, on prend supannEntiteAffectationPrincipale
                if ($libAff === false) {
                    $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
                } elseif (isset($shibbolethAttributes[$libAff])) {
                    $trainee->setService($shibbolethAttributes[$libAff]);
                } else
                    $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);

                $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
                $bap = "";
                $activites = explode(";", (string) $shibbolethAttributes['supannActivite']);
                foreach($activites as $activite) {
                    $pos = stripos($activite, "{BAP}");
                    if ($pos !== false) {
                        $bap = ltrim($activite, "{BAP}");
                        // si {BAP} est trouvé, on arrête
                        break;
                    }
                }

                $trainee->setBap($bap);
                $spCorps = explode(";", (string) $shibbolethAttributes['supannEmpCorps']);
                foreach($spCorps as $spCorp) {
                    $pos = stripos($spCorp, "{NCORPS}");
                    if ($pos !== false) {
                        $corps = ltrim($spCorp, "{NCORPS}");
                        if (ctype_digit($corps))
                            $corps = (int)$corps;

                        $n_corps = $this->managerRegistry->getRepository(\App\Entity\Back\Corps::class)->findOneBy(
                            ['corps' => $corps]
                        );
                        if ($n_corps != null) {
                            $trainee->setCorps($n_corps->getLibelleLong());
                            $trainee->setCategory($n_corps->getCategory());
                        }

                        // si {NCORPS} est trouvé, on arrête
                        break;
                    }
                }
            }

            // Mise à jour du profil en base de données
            $em = $managerRegistry->getManager();
            $em->flush();
            // redirect user to registrations pages
            //$url = $this->generateUrl('front.account.registrations');
            $url = $this->generateUrl('front.program.myprogram');

        }
        else {
            // redirect user to registration form
            $url = $this->generateUrl('front.account.register');
        }

        return new RedirectResponse($url);
    }

    /**
     *
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    #[Route(path: '/profile', name: 'front.account.profile')]
    public function profile(Request $request, ManagerRegistry $managerRegistry): \Symfony\Component\HttpFoundation\Response
    {
        $city = null;

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');
        $corrFormActif = $this->getParameter('corresp_form_actif');

        // Mise à jour du profil avec les attributs récupérés par Shibboleth
        $shibbolethAttributes = $this->getUser()->getCredentials();
        $userEmail = $this->getUser()->getCredentials()['mail'];
        $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findOneBy(['email' => $userEmail]);
        $trainee = $arTrainee;

        $trainee->setShibbolethpersistentid($shibbolethAttributes['eppn']);
        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite'] == '')
            $shibbolethAttributes['supannCivilite'] = 'M.';

        $trainee->setTitle($managerRegistry->getRepository(\App\Entity\Term\Title::class)->findOneBy(
            ['name' => $shibbolethAttributes['supannCivilite']]
        ));

        // Gestion du sn et du givenname multivalués
        $sn = explode(";", $shibbolethAttributes['sn']);
        $givenName = explode(";", $shibbolethAttributes['givenName']);
        $trainee->setLastName($sn[0]);
        $trainee->setFirstName($givenName[0]);
        $trainee->setEmail($shibbolethAttributes['mail']);
        //$trainee->setBirthDate($shibbolethAttributes['schacDateOfBirth']);
        $datenaiss = str_replace("-", "", (string) $shibbolethAttributes['supannOIDCDateDeNaissance']);
        $trainee->setBirthDate($datenaiss);
        // Mise en forme adresse au cas où il y en a une
        if (($adresseFromLdap == true) && ($shibbolethAttributes['postalAddress'] != "")) {
            $address = $shibbolethAttributes['postalAddress'];
            // Recupération du code postal
            preg_match('#\$\d{5}#', (string) $address, $result, PREG_OFFSET_CAPTURE, 3);
            if (isset($result[0][0])) {
                $codepostal = substr($result[0][0], 1);
                // Récupération position du dernier $ dans la chaine
                $posLast = strripos((string) $address, "$");
                // Adresse = début de la chaîne jusqu'au code postal
                $addressPro = substr((string) $address, 0, $result[0][1]);
                // On retire les '$' restants dans l'adresse
                $addressPro = str_replace("$", " / ", $addressPro);
                if (isset($result[0][1])) {
                    if ($posLast == $result[0][1]) {
                        // Si il n'y a pas de pays renseigné
                        $city = substr((string) $address, $result[0][1] + 6);
                    } else {
                        // Si il y a un pays, on recupère seulement la partie ville
                        $city = substr((string) $address, $result[0][1] + 6, $posLast - $result[0][1] - 6);
                    }

                    $trainee->setCity($city);
                }

                $trainee->setAddress($addressPro);
                $trainee->setZip($codepostal);
            }
        }

        $trainee->setPhoneNumber($shibbolethAttributes['telephoneNumber']);
		$shibbolethAttributes['primary-affiliation'] = strtolower($shibbolethAttributes['primary-affiliation']);
        if ($shibbolethAttributes['primary-affiliation'] == "staff") {
            // Transformation de l'attribut 'staff' en 'employee'
            $shibbolethAttributes['primary-affiliation'] = "employee";
        }

        // on teste si biatss : si oui, supérieur hiérarchique obligatoire dans le formulaire
        $flagSupRequired = false;
        if ($shibbolethAttributes['primary-affiliation'] == "employee") {
            $flagSupRequired = true;
        }

        $publictype = $managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
            ['machinename' => $shibbolethAttributes['primary-affiliation']]
        );
        if ($publictype != null) {
            // cas general
            $trainee->setPublictype($publictype);
        } elseif ($shibbolethAttributes['primary-affiliation'] == 'student') {
            // cas des etudiants doctorants
            $flagDoc = 0;
            if ($shibbolethAttributes['supannEtuCursusAnnee'] != "") {
                // Test si doctorant sur supannEtuCursusAnnee
                $tabCursus = $shibbolethAttributes['supannEtuCursusAnnee'];
                if (is_array($tabCursus)) {
                    foreach ($tabCursus as $tabCursu) {
                        if (str_contains((string) $tabCursu, '{SUPANN}D')) {
                            // c'est un doctorant
                            $flagDoc = 1;
                            $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                                ['name' => 'enseignant']
                            ));
                            break;
                        }
                    }
                } elseif (str_contains((string) $tabCursus, '{SUPANN}D')) {
                    // c'est un doctorant
                    $flagDoc = 1;
                    $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                        ['name' => 'enseignant']
                    ));
                }
            }
            // si pas trouvé sur supannEtuCursusAnnee, test sur unscoped-affiliation
            if ($flagDoc == 0) {
                // si etudiant, on regarde aussi edupersonaffiliation pour détecter les doctorants
                $affiliation = explode(';', (string) $shibbolethAttributes['unscoped-affiliation']);
                // Recup des types de public possibles
                $allPublictypes = $managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findAll();
                foreach ($affiliation as $aff) {
                    foreach ($allPublictypes as $allPublictype) {
                        if ($aff == $allPublictype->getMachinename()) {
                            $flagDoc = 1;
                            $trainee->setPublictype($allPublictype);
                            break 2;
                        }
                    }
                }
            }
            if ($flagDoc == 0) {
                // Etudiant 'simple', pas doctorant -> n'a pas accès à l'application
                $this->addFlash('error', 'Vous ne pouvez pas vous inscrire sur Geforp. La plate-forme n\'est pas accessible aux étudiants.');
                return $this->redirectToRoute('front.public.index');
            }
        } else {
            $trainee->setPublictype($managerRegistry->getRepository(\App\Entity\Term\Publictype::class)->findOneBy(
                ['machinename' => 'other']
            ));
        }

        // Etablissement
        $flagEtab = 0;
        $listeEtab = $managerRegistry->getRepository(\App\Entity\Back\Institution::class)->findAll();
        $eppn = $shibbolethAttributes['eppn'];
        if (stripos((string) $eppn , "@")>0) {
            // recup domaine dans l'eppn
            $domaine = substr((string) $eppn, stripos((string) $eppn, "@") + 1);
            foreach ($listeEtab as $etab) {
                $domaines = $etab->getDomains();
                foreach ($domaines as $dom) {
                    // test domaine de l'eppn et domaines renseignés pour les établissements définis en BDD
                    if (strtolower((string) $dom->getName()) === strtolower($domaine)) {
                        $trainee->setInstitution($etab);
                        $flagEtab = 1;
                        break 2;
                    }
                }
            }
        }

        if ($flagEtab !== 1) {
            // Pb pas d'etablissement defini -> message d'erreur pour le stagiaire
            $this->addFlash('error', 'Vous ne pouvez pas vous inscrire sur Geforp. Votre établissement n\'a pas accès à la plate-forme.');
            return $this->redirectToRoute('front.public.index');

        }

        $flagAMU = 0;
        // Attributs AMU
        if ($trainee->getInstitution()->getName() == "AMU") {
            // tag de l'utilisateur comme étant AMU
            $flagAMU = 1;
            $servicelib = $shibbolethAttributes['amuAffectationLib'];
            $trainee->setService($servicelib);
            $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
            //$trainee->setBap($shibbolethAttributes['amuBap']);
            $trainee->setCampus($shibbolethAttributes['amuCampus']);
            $bap = "";
            $activites = explode(";", (string) $shibbolethAttributes['supannActivite']);
            foreach ($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }

            $trainee->setBap($bap);
            $spCorps = explode(";", (string) $shibbolethAttributes['supannEmpCorps']);
            foreach($spCorps as $spCorp) {
                $pos = stripos($spCorp, "{NCORPS}");
                if ($pos !== false) {
                    $corps = ltrim($spCorp, "{NCORPS}");
                    if (ctype_digit($corps))
                        $corps = (int)$corps;

                    $n_corps = $this->managerRegistry->getRepository(\App\Entity\Back\Corps::class)->findOneBy(
                        ['corps' => $corps]
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
                    }

                    // si {NCORPS} est trouvé, on arrête
                    break;
                }
            }
        } else {
            $libAff = $this->getParameter('lib_affectation');
            // si le libellé pour l'affection principale n'est pas précisé, on prend supannEntiteAffectationPrincipale
            if ($libAff === false) {
                $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
            } elseif (isset($shibbolethAttributes[$libAff])) {
                $trainee->setService($shibbolethAttributes[$libAff]);
            } else
                $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);

            $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
            $bap = "";
            $activites = explode(";", (string) $shibbolethAttributes['supannActivite']);
            foreach ($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }

            $trainee->setBap($bap);
            $spCorps = explode(";", (string) $shibbolethAttributes['supannEmpCorps']);
            foreach($spCorps as $spCorp) {
                $pos = stripos($spCorp, "{NCORPS}");
                if ($pos !== false) {
                    $corps = ltrim($spCorp, "{NCORPS}");
                    if (ctype_digit($corps))
                        $corps = (int)$corps;

                    $n_corps = $this->managerRegistry->getRepository(\App\Entity\Back\Corps::class)->findOneBy(
                        ['corps' => $corps]
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
                    }

                    // si {NCORPS} est trouvé, on arrête
                    break;
                }
            }
        }

        $form = $this->createForm(ProfileType::class, $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // TEST sur le responsable
                if ($trainee->getEmailSup()) {
                    // Vérification du mail qui doit être institutionnel
                    if (stripos((string) $trainee->getEmailSup(), "@") > 0) {
                        $domaine = substr((string) $trainee->getEmailsup(), stripos((string) $trainee->getEmailsup(), "@") + 1);
                        $domaines = $trainee->getInstitution()->getDomains();
                        $listeDomaines = [];
                        foreach ($domaines as $dom) {
                            $listeDomaines[$dom->getName()] = $dom;
                        }

                        if (array_key_exists($domaine, $listeDomaines)) {
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de clui du stagiaire
                            if (strtolower((string) $trainee->getEmailSup()) === strtolower((string) $trainee->getEmail())) {
                                $this->addFlash('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $em = $managerRegistry->getManager();
                                $em->flush();
                                $this->addFlash('success', 'Votre profil a été mis à jour.');
                            }
                        } else {
                            $this->addFlash('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                    }
                } else {
                    $em = $managerRegistry->getManager();
                    $em->flush();
                    $this->addFlash('success', 'Votre profil a été mis à jour.');
                }
            }
        }

        return $this->render('Front/Account/profile/profile.html.twig',['user' => $trainee, 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName()]);
    }

    /**
     *
     *
     * @return array
     */
    #[Route(path: '/logout/{return}', name: 'front.account.logout', requirements: ['return' => '.+'])]
    public function logout(Request $request, string $return = null): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        return $this->redirect($this->get('shibboleth')->getLogoutUrl($request, $return ?: $this->generateUrl('front.public.index')));
    }
}
