<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:43 AM
 */

namespace App\Controller\Front;


use App\AccessRight\AccessRightRegistry;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;
use App\Form\Type\ProfileType;
use App\Entity\Back\Trainee;
use App\Entity\Back\SupannCodeEntite;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * This controller regroup all public actions relative to account.
 *
 * @Route("/account")
 */
class AnonymousAccountController extends AbstractController
{
    protected $traineeClass = Trainee::class;

    /**
     * @param $string
     * @param $tiret
     * @return string
     */
    protected function enleveAccents($string, $tiret = null)
    {
        $string = utf8_encode($string);
        $string = htmlentities( $string, ENT_NOQUOTES, 'utf-8' );
        $string = preg_replace( '#&([A-Za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $string );
        $string = preg_replace( '#&([A-Za-z]{2})(?:lig);#', '\1', $string );
        $string = preg_replace( '#&[^;]+;#', '', $string );

        /*
         * Supprime tous les espaces et caractères bizares
         */
        $string = trim($string);

        if ($tiret) {
            $tabCar = array(" ", "\t", "\n", "\r", "\0", "\x0B", "\xA0", "-", "_");
        } else {
            $tabCar = array(" ", "\t", "\n", "\r", "\0", "\x0B", "\xA0");
        }

        $string = str_replace($tabCar, array(), $string);
        return ($string);
    }


    /**
     * Register a new account with data.
     *
     * @Route("/register", name="front.account.register")
     * @Template("Front/Account/profile/account-registration.html.twig")
     * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
     */
    public function registerAction(Request $request, ManagerRegistry $doctrine, AccessRightRegistry $accessRightRegistry)
    {
        $trainee = new Trainee();

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');
        $corrFormActif = $this->getParameter('corresp_form_actif');

        $shibbolethAttributes = $this->getUser()->getCredentials();

        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite']=='')
            $shibbolethAttributes['supannCivilite'] = 'M.';
        $trainee->setTitle($doctrine->getRepository('App\Entity\Term\Title')->findOneBy(
            array('name' => $shibbolethAttributes['supannCivilite'])
        ));
        $trainee->setLastname($shibbolethAttributes['sn']);
        $trainee->setFirstname($shibbolethAttributes['givenName']);
        $trainee->setEmail($shibbolethAttributes['mail']);
        $datenaiss = str_replace("-", "", $shibbolethAttributes['supannOIDCDateDeNaissance']);
        $trainee->setBirthdate($datenaiss);
        // Mise en forme adresse au cas où il y en a une
        if (($adresseFromLdap == true) && ($shibbolethAttributes['postalAddress']!="")) {
            $address = $shibbolethAttributes['postalAddress'];
            // Recupération du code postal
            preg_match('/\$[0-9]{5}/', $address, $result, PREG_OFFSET_CAPTURE, 3);
            $codepostal = substr($result[0][0], 1);
            // Récupération position du dernier $ dans la chaine
            $posLast = strripos($address, "$");
            // Adresse = début de la chaîne jusqu'au code postal
            $addressPro = substr($address, 0, $result[0][1]);
            // On retire les '$' restants dans l'adresse
            $addressPro = str_replace("$", " / ", $addressPro);
            if ($posLast == $result[0][1]) {
                // Si il n'y a pas de pays renseigné
                $city = substr($address, $result[0][1] + 6);
            } else {
                // Si il y a un pays, on recupère seulement la partie ville
                $city = substr($address, $result[0][1] + 6, $posLast - $result[0][1] - 6);
            }
            $trainee->setAddress($addressPro);
            $trainee->setCity($city);
            $trainee->setZip($codepostal);
        }
        $trainee->setPhonenumber($shibbolethAttributes['telephoneNumber']);
        if ($shibbolethAttributes['primary-affiliation'] == "staff") {
            // Transformation de l'attribut 'staff' en 'employee'
            $shibbolethAttributes['primary-affiliation'] = "employee";
        }
        $primary_affiliation = $doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
            array('machinename' => $shibbolethAttributes['primary-affiliation'])
        );

        if ($primary_affiliation != null) {
            // cas general
            $trainee->setPublictype($primary_affiliation);
        } else {
            // cas des etudiants doctorants
            if ($shibbolethAttributes['primary-affiliation'] == 'student') {
                $flagDoc = 0;

                if (isset($shibbolethAttributes['supannEtuCursusAnnee'])) {
                    // Test si doctorant sur supannEtuCursusAnnee
                    $tabCursus = $shibbolethAttributes['supannEtuCursusAnnee'];
                    foreach ($tabCursus as $cursus) {
                        if (strpos($cursus, 'D') !== false) {
                            // c'est un doctorant
                            $flagDoc = 1;
                            $trainee->setPublictype($doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
                                array('name' => 'enseignant')
                            ));
                            break;
                        }
                    }
                }

                // si pas trouvé sur supannEtuCursusAnnee, test sur unscoped-affiliation
                if ($flagDoc == 0) {
                    // si etudiant, on regarde aussi edupersonaffiliation pour détecter les doctorants
                    $affiliation = explode(';', $shibbolethAttributes['unscoped-affiliation']);
                    // Recup des types de public possibles
                    $allPublictypes = $doctrine->getRepository('App\Entity\Term\Publictype')->findAll();
                    foreach ($affiliation as $aff) {
                        foreach ($allPublictypes as $pubtype) {
                            if ($aff == $pubtype->getMachinename()) {
                                $flagDoc = 1;
                                $trainee->setPublictype($pubtype);
                                break 2;
                            }
                        }
                    }
                }

                if ($flagDoc == 0) {
                    // Etudiant 'simple', pas doctorant -> n'a pas accès à l'application
                    $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas vous inscrire sur Geforp. La plate-forme n\'est pas accessible aux étudiants.');
                    return $this->redirectToRoute('front.public.index');
                }
            } else {
                $trainee->setPublictype($doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
                    array('machinename' => 'other')
                ));
            }
        }

        // Etablissement
        $flagEtab = 0;
        $listeEtab = $doctrine->getRepository('App\Entity\Back\Institution')->findAll();
        $eppn = $shibbolethAttributes['eppn'];
        if (stripos($eppn , "@")>0) {
            // recup domaine dans l'eppn
            $domaine = substr($eppn, stripos($eppn, "@") + 1);
            foreach ($listeEtab as $etab) {
                $domaines = $etab->getDomains();
                foreach ($domaines as $dom) {
                    // test domaine de l'eppn et domaines renseignés pour les établissements définis en BDD
                    if (strtolower($dom->getName()) == strtolower($domaine)) {
                        $trainee->setInstitution($etab);
                        $flagEtab = 1;
                        break 2;
                    }
                }
            }
        }
        if ($flagEtab !== 1) {
            // Pb pas d'etablissement defini -> message d'erreur pour le stagiaire
            $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas vous inscrire sur Geforp. Votre établissement n\'a pas accès à la plate-forme.');
            return $this->redirectToRoute('front.public.index');

        }

        $flagAMU = 0;
        // Attributs AMU
        if ($trainee->getInstitution()->getName() == "AMU") {
            // tag de l'utilisateur comme étant AMU
            $flagAMU = 1;

            $trainee->setService($shibbolethAttributes['amuAffectationLib']);
            $trainee->setAmustatut($shibbolethAttributes['supannCodePopulation']);
            $bap = "";
            $activites = explode(";", $shibbolethAttributes['supannActivite']);
            foreach($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }
            $trainee->setBap($bap);
            $spCorps = explode(";", $shibbolethAttributes['supannEmpCorps']);
            foreach($spCorps as $spCorp) {
                $pos = stripos($spCorp, "{NCORPS}");
                if ($pos !== false) {
                    $corps = ltrim($spCorp, "{NCORPS}");
                    if (ctype_digit($corps))
                        $corps = (int)$corps;
                    $n_corps = $this->getDoctrine()->getRepository('App\Entity\Back\Corps')->findOneBy(
                        array('corps' => $corps)
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
            if ($libAff === false)
                $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
            else {
                if (isset($shibbolethAttributes[$libAff]))
                    $trainee->setService($shibbolethAttributes[$libAff]);
                else
                    $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
            }

            $bap = "";
            $activites = explode(";", $shibbolethAttributes['supannActivite']);
            foreach($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }
            $trainee->setBap($bap);
            $trainee->setAmustatut($shibbolethAttributes['supannCodePopulation']);

            $spCorps = explode(";", $shibbolethAttributes['supannEmpCorps']);
            foreach($spCorps as $spCorp) {
                $pos = stripos($spCorp, "{NCORPS}");
                if ($pos !== false) {
                    $corps = ltrim($spCorp, "{NCORPS}");
                    if (ctype_digit($corps))
                        $corps = (int)$corps;
                    $n_corps = $this->getDoctrine()->getRepository('App\Entity\Back\Corps')->findOneBy(
                        array('corps' => $corps)
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

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // TEST sur le responsable
                if ($trainee->getEmailsup()) {
                    // Vérification du mail qui doit être institutionnel
                    if (stripos($trainee->getEmailsup() , "@")>0) {
                        $domaine = substr($trainee->getEmailsup(), stripos($trainee->getEmailsup(), "@") + 1);
                        $domaines = $trainee->getInstitution()->getDomains();
                        $listeDomaines = array();
                        foreach ($domaines as $dom) {
                            $listeDomaines[$dom->getName()] = $dom;
                        }
                        // Association nom de domaine et établissement
                        if (array_key_exists($domaine, $listeDomaines)){
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de celui du stagiaire
                            if (strtolower($trainee->getEmailsup()) == strtolower($trainee->getEmail())) {
                                $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $this->registerShibbolethTrainee($this->getUser()->getCredentials(), $trainee, true);
                                $trainee->setCreatedAt(new \DateTime('now'));
                                $trainee->setUpdatedAt(new \DateTime('now'));

                                $em = $doctrine->getManager();
                                $em->persist($trainee);
                                $em->flush();
                                $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');

                                return $this->redirectToRoute('front.program.myprogram');

                            }
                        }else {
                            $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }

                    }
                } else {
                    $this->registerShibbolethTrainee($this->getUser()->getCredentials(), $trainee, true);
                    $trainee->setCreatedAt(new \DateTime('now'));
                    $trainee->setUpdatedAt(new \DateTime('now'));

                    $em = $doctrine->getManager();
                    $em->persist($trainee);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');

                    return $this->redirectToRoute('front.program.myprogram');
                }
            }
        }

        return array('user' => $this->getUser(), 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName());
    }

    /**
     * Return true if there is an account with the specified email.
     */
    protected function emailCheck($cred, ManagerRegistry $doctrine)
    {
        $em    = $doctrine->getManager();
        $email = $cred['email'];
        if( ! $email) {
            return array('exists' => false);
        }
        $trainee = $em->getRepository(Trainee::class)->findByEmail($email);

        return array('exists' => $trainee ? true : false);
    }

    /**
     * @param $cred
     * @param $trainee
     * @param boolean
     */
    protected function registerShibbolethTrainee($cred, $trainee, $shibboleth)
    {
        $trainee->setIsActive(false);

        if ($shibboleth) {
            // if shibboleth, save persistent_id and force mail
            // and set active to true
            $persistentId = $cred['persistent-id'];
            $email        = $cred['mail'];
            $eppn = $cred['eppn'];
            $trainee->setShibbolethpersistentid($eppn ? $eppn : $email);
            $trainee->setEmail($email);
            $trainee->setIsActive(true);
        }

    }

}
