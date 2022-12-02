<?php
/**
 * Created by PhpStorm.
 */

namespace App\Controller\Front;


use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\Title;
use Doctrine\Persistence\ManagerRegistry;
use App\Form\Type\ProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Back\Trainee;
use App\Entity\Back\SupannCodeEntite;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

/**
 * @Route("/account")
 * @Security("is_granted('IS_AUTHENTICATED_FULLY')")
 */
class AccountController extends AbstractController
{
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
     * @Route("/", name="front.account")
     *
     * @return RedirectResponse
     */
    public function accountAction(Request $request, ManagerRegistry $doctrine)
    {
        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');

        // Récupération des attributs Shibboleth pour mise à jour du profil
        $shibbolethAttributes = $this->getUser()->getCredentials();

        //$trainee = $this->getUser();
        $userEmail = $this->getUser()->getCredentials()['mail'];
        // on utilise l'eppn comme persistent-id
        //$userPersitentId = $this->getUser()->getCredentials()['persistent-id'];
        $userPersitentId = $this->getUser()->getCredentials()['eppn'];

        if (isset($userPersitentId)) {
            $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findBy(array("shibbolethpersistentid" => $userPersitentId));
            if (isset($arTrainee[0])) {
            }else {
                if (isset($userEmail)) {
                    $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($userEmail);
                    // Il y a bien un stagiaire en base, mais il ne s'est jamais connecté par Shibboleth -> on force l'envoi vers le profil pour renseigner le N+1
                    $url = $this->generateUrl('front.account.profile');
                    return new RedirectResponse($url);
                }
            }

        } else {
            if (isset($userEmail)) {
                $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($userEmail);
            }
        }

        if (isset($arTrainee[0])) {
            $trainee = $arTrainee[0];

            // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
            if ($shibbolethAttributes['supannCivilite']=='')
                $shibbolethAttributes['supannCivilite'] = 'M.';
            $trainee->setTitle($doctrine->getRepository('App\Entity\Term\Title')->findOneBy(
                array('name' => $shibbolethAttributes['supannCivilite'])
            ));
            $trainee->setLastName($shibbolethAttributes['sn']);
            $trainee->setFirstName($shibbolethAttributes['givenName']);
            $trainee->setEmail($shibbolethAttributes['mail']);
            $datenaiss = str_replace("-", "", $shibbolethAttributes['supannOIDCDateDeNaissance']);
            $trainee->setBirthDate($datenaiss);
            // Mise en forme adresse au cas où il y en a une
            if (($adresseFromLdap == true) && ($shibbolethAttributes['postalAddress']!="")) {
                $address = $shibbolethAttributes['postalAddress'];
                // Recupération du code postal
                preg_match('/\$[0-9]{5}/', $address, $result, PREG_OFFSET_CAPTURE, 3);
                if (isset($result[0][0]) && isset($result[0][1])) {

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
            }
            $trainee->setPhoneNumber($shibbolethAttributes['telephoneNumber']);
            if ($shibbolethAttributes['primary-affiliation'] == "staff") {
                // Transformation de l'attribut 'staff' en 'employee'
                $shibbolethAttributes['primary-affiliation'] = "employee";
            }
            $primary_affiliation = $doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
                array('machinename' => $shibbolethAttributes['primary-affiliation'])
            );
            if ($primary_affiliation != null) {
                $trainee->setPublictype($primary_affiliation);
            }
            else {
                $trainee->setPublictype($doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
                    array('machinename' => 'other')
                ));
            }

            // Etablissement
            $flagEtab = 0;
            $persitentId = $shibbolethAttributes['persistent-id'];
            $listeEtab = $doctrine->getRepository('App\Entity\Back\Institution')->findAll();
            foreach ($listeEtab as $etab) {
                $idp = $etab->getIdp();
                if(strpos($persitentId, $idp) !== false) {
                    // Si on trouve l'idp de l'etablissement dans le shibboleth persistent id, alors on definit l'etablissement du stagiaire
                    $trainee->setInstitution($etab);
                    $flagEtab = 1;
                    break;
                }
            }
            if ($flagEtab !== 1) {
                // Pb pas d'etablissement defini -> message d'erreur pour le stagiaire

            }

            // Attributs AMU
            if ($trainee->getInstitution()->getName() == "AMU") {
                $trainee->setService($shibbolethAttributes['amuAffectationLib']);
                $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
                //$trainee->setBap($shibbolethAttributes['amuBap']);
                $trainee->setCampus($shibbolethAttributes['amuCampus']);
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
                $corps = ltrim($shibbolethAttributes['supannEmpCorps'], "{NCORPS}");
                // Si on a une valeur, on cherche le libellé et la catégorie dans la table
                if (isset($corps)) {
                    if (ctype_digit($corps))
                        $corps = (int)$corps;
                    $n_corps = $doctrine->getRepository('App\Entity\Back\Corps')->findOneBy(
                        array('corps' => $corps)
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
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

                $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
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
                $corps = ltrim($shibbolethAttributes['supannEmpCorps'], "{NCORPS}");
                // Si on a une valeur, on cherche le libellé et la catégorie dans la table
                if (isset($corps)) {
                    if (ctype_digit($corps))
                        $corps = (int)$corps;
                    $n_corps = $doctrine->getRepository('App\Entity\Back\Corps')->findOneBy(
                        array('corps' => $corps)
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
                    }
                }
            }

            // Mise à jour du profil en base de données
            $em = $doctrine->getManager();
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
     * @param Request $request
     *
     * @Route("/profile", name="front.account.profile")
     * @Template("Front/Account/profile/profile.html.twig")
     *
     * @return array
     */
    public function profileAction(Request $request, ManagerRegistry $doctrine)
    {
        $options = array();

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');
        $corrFormActif = $this->getParameter('corresp_form_actif');

        // Mise à jour du profil avec les attributs récupérés par Shibboleth
        $shibbolethAttributes = $this->getUser()->getCredentials();
        $userEmail = $this->getUser()->getCredentials()['mail'];
        $arTrainee = $doctrine->getRepository('App\Entity\Back\Trainee')->findByEmail($userEmail);
        $trainee = $arTrainee[0];

        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite'] == '')
            $shibbolethAttributes['supannCivilite'] = 'M.';
        $trainee->setTitle($doctrine->getRepository('App\Entity\Term\Title')->findOneBy(
            array('name' => $shibbolethAttributes['supannCivilite'])
        ));
        $trainee->setLastName($shibbolethAttributes['sn']);
        $trainee->setFirstName($shibbolethAttributes['givenName']);
        $trainee->setEmail($shibbolethAttributes['mail']);
        //$trainee->setBirthDate($shibbolethAttributes['schacDateOfBirth']);
        $datenaiss = str_replace("-", "", $shibbolethAttributes['supannOIDCDateDeNaissance']);
        $trainee->setBirthDate($datenaiss);
        // Mise en forme adresse au cas où il y en a une
        if (($adresseFromLdap == true) && ($shibbolethAttributes['postalAddress'] != "")) {
            $address = $shibbolethAttributes['postalAddress'];
            // Recupération du code postal
            preg_match('/\$[0-9]{5}/', $address, $result, PREG_OFFSET_CAPTURE, 3);
            if (isset($result[0][0])) {
                $codepostal = substr($result[0][0], 1);
                // Récupération position du dernier $ dans la chaine
                $posLast = strripos($address, "$");
                // Adresse = début de la chaîne jusqu'au code postal
                $addressPro = substr($address, 0, $result[0][1]);
                // On retire les '$' restants dans l'adresse
                $addressPro = str_replace("$", " / ", $addressPro);
                if (isset($result[0][1])) {
                    if ($posLast == $result[0][1]) {
                        // Si il n'y a pas de pays renseigné
                        $city = substr($address, $result[0][1] + 6);
                    } else {
                        // Si il y a un pays, on recupère seulement la partie ville
                        $city = substr($address, $result[0][1] + 6, $posLast - $result[0][1] - 6);
                    }
                    $trainee->setCity($city);
                }
                $trainee->setAddress($addressPro);
                $trainee->setZip($codepostal);
            }
        }
        $trainee->setPhoneNumber($shibbolethAttributes['telephoneNumber']);
        if ($shibbolethAttributes['primary-affiliation'] == "staff") {
            // Transformation de l'attribut 'staff' en 'employee'
            $shibbolethAttributes['primary-affiliation'] = "employee";
        }
        // on teste si biatss : si oui, supérieur hiérarchique obligatoire dans le formulaire
        $flagSupRequired = false;
        if ($shibbolethAttributes['primary-affiliation'] == "employee") {
            $flagSupRequired = true;
        }
        $primary_affiliation = $doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
            array('machinename' => $shibbolethAttributes['primary-affiliation'])
        );
        if ($primary_affiliation != null) {
            $trainee->setPublictype($primary_affiliation);
        } else {
            $trainee->setPublictype($doctrine->getRepository('App\Entity\Term\Publictype')->findOneBy(
                array('machinename' => 'other')
            ));
        }

        // Etablissement
        $flagEtab = 0;
        $persitentId = $shibbolethAttributes['persistent-id'];
        $listeEtab = $doctrine->getRepository('App\Entity\Back\Institution')->findAll();
        foreach ($listeEtab as $etab) {
            $idp = $etab->getIdp();
            if(strpos($persitentId, $idp) !== false) {
                // Si on trouve l'idp de l'etablissement dans le shibboleth persistent id, alors on definit l'etablissement du stagiaire
                $trainee->setInstitution($etab);
                $flagEtab = 1;
                break;
            }
        }
        if ($flagEtab !== 1) {
            // Pb pas d'etablissement defini -> message d'erreur pour le stagiaire

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
            $activites = explode(";", $shibbolethAttributes['supannActivite']);
            foreach ($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }
            $trainee->setBap($bap);
            $corps = ltrim($shibbolethAttributes['supannEmpCorps'], "{NCORPS}");
            // Si on a une valeur, on cherche le libellé et la catégorie dans la table
            if (isset($corps)) {
                if (ctype_digit($corps))
                    $corps = (int)$corps;
                $n_corps = $doctrine->getRepository('App\Entity\Back\Corps')->findOneBy(
                    array('corps' => $corps)
                );
                if ($n_corps != null) {
                    $trainee->setCorps($n_corps->getLibelleLong());
                    $trainee->setCategory($n_corps->getCategory());
                }
            }
        } else {
            $libAff = $this->container->getParameter('lib_affectation');
            // si le libellé pour l'affection principale n'est pas précisé, on prend supannEntiteAffectationPrincipale
            if ($libAff === false)
                $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
            else {
                if (isset($shibbolethAttributes[$libAff]))
                    $trainee->setService($shibbolethAttributes[$libAff]);
                else
                    $trainee->setService($shibbolethAttributes['supannEntiteAffectationPrincipale']);
            }

            $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
            $bap = "";
            $activites = explode(";", $shibbolethAttributes['supannActivite']);
            foreach ($activites as $activite) {
                $pos = stripos($activite, "{BAP}");
                if ($pos !== false) {
                    $bap = ltrim($activite, "{BAP}");
                    // si {BAP} est trouvé, on arrête
                    break;
                }
            }
            $trainee->setBap($bap);
            $corps = ltrim($shibbolethAttributes['supannEmpCorps'], "{NCORPS}");
            // Si on a une valeur, on cherche le libellé et la catégorie dans la table
            if (isset($corps)) {
                if (ctype_digit($corps))
                    $corps = (int)$corps;
                $n_corps = $doctrine->getRepository('App\Entity\Back\Corps')->findOneBy(
                    array('corps' => $corps)
                );
                if ($n_corps != null) {
                    $trainee->setCorps($n_corps->getLibelleLong());
                    $trainee->setCategory($n_corps->getCategory());
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
                    if (stripos($trainee->getEmailSup(), "@") > 0) {
                        $domaine = substr($trainee->getEmailsup(), stripos($trainee->getEmailsup(), "@") + 1);
                        $domaines = $trainee->getInstitution()->getDomains();
                        $listeDomaines = array();
                        foreach ($domaines as $dom) {
                            $listeDomaines[$dom->getName()] = $dom;
                        }
                        if (array_key_exists($domaine, $listeDomaines)) {
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de clui du stagiaire
                            if (strtolower($trainee->getEmailSup()) == strtolower($trainee->getEmail())) {
                                $request->getSession()->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $em = $doctrine->getManager();
                                $em->flush();
                                $request->getSession()->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                            }
                        } else {
                            $request->getSession()->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                    }
                } else {
                    $em = $doctrine->getManager();
                    $em->flush();
                    $request->getSession()->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                }
            }
        }

        return array('user' => $trainee, 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName());
    }

    /**
     * @param Request $request
     * @param string $return
     *
     * @Route("/logout/{return}", name="front.account.logout", requirements={"return" = ".+"})
     *
     * @return array
     */
    public function logoutAction(Request $request, $return = null)
    {
        $this->get('security.context')->setToken(null);
        $this->get('request')->getSession()->invalidate();

        return $this->redirect($this->get('shibboleth')->getLogoutUrl($request, $return ? $return : $this->generateUrl('front.public.index')));
    }
}