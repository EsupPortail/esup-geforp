<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 11:00 AM
 */

namespace App\Controller\Front;


use App\Entity\Core\AbstractTrainee;
use Doctrine\Persistence\ManagerRegistry;
use Sygefor\Bundle\FrontBundle\Form\ProfileType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Trainee;
use App\Entity\SupannCodeEntite;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
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
//        $adresseFromLdap = $this->container->getParameter('adresse_from_ldap');
        $adresseFromLdap = false;

        // Récupération des attributs Shibboleth pour mise à jour du profil
        $shibbolethAttributes = $this->getUser()->getCredentials();

        //$trainee = $this->getUser();
        $userEmail = $this->getUser()->getCredentials()['mail'];
        if (isset($userEmail)) {
            $trainee = $doctrine->getRepository('App\Entity\Trainee')->findByEmail($userEmail);
        } 
        if ($trainee) {

            // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
            if ($shibbolethAttributes['supannCivilite']=='')
                $shibbolethAttributes['supannCivilite'] = 'M.';
                $trainee->setTitle($doctrine->getRepository('App\Entity\Core\Term\Title')->findOneBy(
                array('name' => $shibbolethAttributes['supannCivilite'])
            ));
            $trainee->setOrganization($doctrine->getRepository('App\Entity\Organization')->find(1));
            $trainee->setLastName($shibbolethAttributes['sn']);
            $trainee->setFirstName($shibbolethAttributes['givenName']);
            $trainee->setEmail($shibbolethAttributes['mail']);
            //$trainee->setBirthDate($shibbolethAttributes['schacDateOfBirth']);
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
            $primary_affiliation = $doctrine->getRepository('App\Entity\Core\Term\PublicType')->findOneBy(
                array('name' => $shibbolethAttributes['primary-affiliation'])
            );
            if ($primary_affiliation != null) {
                $trainee->setPublicType($primary_affiliation);
            }
            else {
                $trainee->setPublicType($this->getDoctrine()->getRepository('App\Entity\Core\Term\PublicType')->findOneBy(
                    array('name' => 'other')
                ));
            }
//            $trainee->setStatus($shibbolethAttributes['postalCode']);

            // Etablissement
            $eppn = $shibbolethAttributes['eppn'];
            if (stripos($eppn , "@")>0) {
                $domaine = substr($eppn, stripos($eppn, "@") + 1);
                $listeDomaines = $this->getParameter('domaines');
                // Association nom de domaine et établissement
                if (array_key_exists($domaine, $listeDomaines)){
                    $etab = $listeDomaines[$domaine];
                }else {
                    $etab = "AMU";
                }
                $trainee->setInstitution($doctrine->getRepository('App\Entity\Institution')->findOneBy(
                    array('name' => $etab)
                ));

            }

            // Attributs AMU
            if ($trainee->getInstitution()->getName() == "AMU") {
                // Suite à migration SIHAM, on utilise amuAffectationLib
                if (isset($shibbolethAttributes['amuAffectationLib'])) {
                    $servicelib = $shibbolethAttributes['amuAffectationLib'];
                } else {
                    $services = explode(";", $shibbolethAttributes['supannEntiteAffectation']);
                    $servicelib = "";
                    if (count($services) > 0) {
                        foreach ($services as $service) {
                            $supannCodeEntite = $this->getDoctrine()->getRepository('App\Entity\SupannCodeEntite')->findOneBy(
                                array('supannCodeEntite' => $service)
                            );
                            if ($supannCodeEntite != null) {
                                $servicelib .= $supannCodeEntite->getDescription() . " ; ";
                            }
                        }
                    }
                }
                $trainee->setService($servicelib);
                $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
                //$trainee->setBap($shibbolethAttributes['amuBap']);
//                $trainee->setCampus($shibbolethAttributes['amuCampus']);
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
                    $n_corps = $this->getDoctrine()->getRepository('App\Entity\Corps')->findOneBy(
                        array('corps' => $corps)
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
                    }
                }
            }
            else {
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
                    $n_corps = $this->getDoctrine()->getRepository('App\Entity\Corps')->findOneBy(
                        array('corps' => $corps)
                    );
                    if ($n_corps != null) {
                        $trainee->setCorps($n_corps->getLibelleLong());
                        $trainee->setCategory($n_corps->getCategory());
                    }
                }
            }


            if ($trainee->getIsActive()) {
                // Mise à jour du profil en base de données
                $em = $this->getDoctrine()->getManager();
                $em->flush();
                // redirect user to registrations pages
                //$url = $this->generateUrl('front.account.registrations');
                $url = $this->generateUrl('front.public.myprogram');
            }
            else {
                return $this->redirectToRoute('front.account.logout', array('return' => $this->generateUrl('front.public.index', array('shibboleth' => 1, 'error' => 'activation'))));
            }
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
     * @Template("@SygeforFront/Account/profile/profile.html.twig")
     *
     * @return array
     */
    public function profileAction(Request $request)
    {
        $options = array();

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->container->getParameter('adresse_from_ldap');
        $corrFormActif = $this->container->getParameter('corresp_form_actif');

        // Mise à jour du profil avec les attributs récupérés par Shibboleth
        $shibbolethAttributes = $this->get('security.token_storage')->getToken()->getAttributes();
        $trainee = $this->getUser();
        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite']=='')
            $shibbolethAttributes['supannCivilite'] = 'M.';
        $trainee->setTitle($this->getDoctrine()->getRepository('SygeforCoreBundle:PersonTrait\Term\Title')->findOneBy(
            array('name' => $shibbolethAttributes['supannCivilite'])
        ));
        $trainee->setOrganization($this->getDoctrine()->getRepository('SygeforCoreBundle:Organization')->find(1));
        $trainee->setLastName($shibbolethAttributes['sn']);
        $trainee->setFirstName($shibbolethAttributes['givenName']);
        $trainee->setEmail($shibbolethAttributes['mail']);
        //$trainee->setBirthDate($shibbolethAttributes['schacDateOfBirth']);
        $datenaiss = str_replace("-", "", $shibbolethAttributes['supannOIDCDateDeNaissance']);
        $trainee->setBirthDate($datenaiss);
        // Mise en forme adresse au cas où il y en a une
        if (($adresseFromLdap == true) && ($shibbolethAttributes['postalAddress']!="")) {
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
        if ($shibbolethAttributes['primary_affiliation'] == "staff") {
            // Transformation de l'attribut 'staff' en 'employee'
            $shibbolethAttributes['primary_affiliation'] = "employee";
        }
        // on teste si biatss : si oui, supérieur hiérarchique obligatoire dans le formulaire
        $flagSupRequired = false;
        if ($shibbolethAttributes['primary_affiliation'] == "employee") {
            $flagSupRequired = true;
        }
        $primary_affiliation = $this->getDoctrine()->getRepository('Sygefor\Bundle\TraineeBundle\Entity\Term\PublicType')->findOneBy(
            array('name' => $shibbolethAttributes['primary_affiliation'])
        );
        if ($primary_affiliation != null) {
            $trainee->setPublicType($primary_affiliation);
        }
        else {
            $trainee->setPublicType($this->getDoctrine()->getRepository('Sygefor\Bundle\TraineeBundle\Entity\Term\PublicType')->findOneBy(
                array('name' => 'other')
            ));
        }
        $trainee->setStatus($shibbolethAttributes['postalCode']);

        // Etablissement
        $eppn = $shibbolethAttributes['eppn'];
        if (stripos($eppn , "@")>0) {
            $domaine = substr($eppn, stripos($eppn, "@") + 1);
            $listeDomaines = $this->container->getParameter('domaines');
            // Association nom de domaine et établissement
            if (array_key_exists($domaine, $listeDomaines)){
                $etab = $listeDomaines[$domaine];
            }else {
                $etab = "AMU";
            }
            $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                array('name' => $etab)
            ));

/*            switch($domaine) {
                case "univ-amu.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "AMU")
                    ));
                    break;
                case "univ-avignon.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "AVIGNON")
                    ));
                    break;
                case "univ-tln.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "TOULON")
                    ));
                    break;
                case "umontpellier.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "MONTPELLIER")
                    ));
                    break;
                case "unice.fr":
                case "univ-cotedazur.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "NICE")
                    ));
                    break;
                case "centrale-marseille.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "CENTRALE MARSEILLE")
                    ));
                    break;
                case "univ-lr.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "LA ROCHELLE")
                    ));
                    break;
                case "univ-eiffel.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "UNIVERSITE GUSTAVE EIFFEL")
                    ));
                    break;
                case "univ-reims.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "UNIVERSITE REIMS")
                    ));
                    break;
                case "univ-nantes.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "UNIVERSITE NANTES")
                    ));
                    break;
                case "insa-lyon.fr":
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "INSA LYON")
                    ));
                    break;
                default:
                    $trainee->setInstitution($this->getDoctrine()->getRepository('Sygefor\Bundle\InstitutionBundle\Entity\AbstractInstitution')->findOneBy(
                        array('name' => "AMU")
                    ));
                    break;
            }*/
        }

        $flagAMU = 0;
        // Attributs AMU
        if ($trainee->getInstitution()->getName() == "AMU") {
            // tag de l'utilisateur comme étant AMU
            $flagAMU = 1;
            //Suite à migration SIHAM, on utilise amuAffectationLib
            /*$services = explode(";", $shibbolethAttributes['supannEntiteAffectation']);
            $servicelib = "";
            if (count($services) > 0) {
                foreach ($services as $service) {
                    $supannCodeEntite = $this->getDoctrine()->getRepository('SygeforMyCompanyBundle:SupannCodeEntite')->findOneBy(
                        array('supannCodeEntite' => $service)
                    );
                    if ($supannCodeEntite != null) {
                        $servicelib .= $supannCodeEntite->getDescription() . " ; ";
                    }
                }
            }*/
            $servicelib = $shibbolethAttributes['amuAffectationLib'];
            $trainee->setService($servicelib);
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
            if (count($corps > 0)) {
                if (ctype_digit($corps))
                    $corps = (int)$corps;
                $n_corps = $this->getDoctrine()->getRepository('SygeforMyCompanyBundle:Corps')->findOneBy(
                    array('corps' => $corps)
                );
                if ($n_corps != null) {
                    $trainee->setCorps($n_corps->getLibelleLong());
                    $trainee->setCategory($n_corps->getCategory());
                }
            }
        }
        else {
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
            if (count($corps > 0)) {
                if (ctype_digit($corps))
                    $corps = (int)$corps;
                $n_corps = $this->getDoctrine()->getRepository('SygeforMyCompanyBundle:Corps')->findOneBy(
                    array('corps' => $corps)
                );
                if ($n_corps != null) {
                    $trainee->setCorps($n_corps->getLibelleLong());
                    $trainee->setCategory($n_corps->getCategory());
                }
            }
        }

        $form = $this->createForm(new ProfileType($this->get('sygefor_core.access_right_registry')), $trainee);
        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // TEST sur le responsable
                if (count($trainee->getEmailSup())>0) {
                    // Vérification du mail qui doit être institutionnel
                    if (stripos($trainee->getEmailSup() , "@")>0) {
                        $domaine = substr($trainee->getEmailSup(), stripos($trainee->getEmailSup(), "@") + 1);
                        $listeDomaines = $this->container->getParameter('domaines');
                        // Association nom de domaine et établissement
                        if (array_key_exists($domaine, $listeDomaines)){
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de clui du stagiaire
                            if (strtolower($trainee->getEmailSup()) == strtolower($trainee->getEmail())) {
                                $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $em = $this->getDoctrine()->getManager();
                                $em->flush();
                                $this->get('session')->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                            }
                        }else {
                            $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                    }
                } else {
                    $em = $this->getDoctrine()->getManager();
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                }
            }
        }

        return array('user' => $this->getUser(), 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName());
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