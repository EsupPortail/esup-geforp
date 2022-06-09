<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:43 AM
 */

namespace App\Controller\Front;


use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;
use App\Controller\Front\AbstractAnonymousAccountController;
use App\Form\Type\ProfileType;
use App\Entity\Trainee;
use App\Entity\SupannCodeEntite;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * This controller regroup all public actions relative to account.
 *
 * @Route("/account")
 */
class AnonymousAccountController extends AbstractAnonymousAccountController
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
     */
    public function registerAction(Request $request, ManagerRegistry $doctrine)
    {
        $trainee = new Trainee();

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');
        $corrFormActif = $this->getParameter('corresp_form_actif');

        $shibbolethAttributes = $this->getUser()->getCredentials();

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
        $primary_affiliation = $this->getDoctrine()->getRepository('App\Entity\Core\Term\PublicType')->findOneBy(
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
//        $trainee->setStatus($shibbolethAttributes['postalCode']);


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

        $flagAMU = 0;
        // Attributs AMU
        if ($trainee->getInstitution()->getName() == "AMU") {
            // tag de l'utilisateur comme étant AMU
            $flagAMU = 1;
            /* Suite à migration SIHAM, on utilise directement l'attribut amuaffectationlib
            $services = explode(";", $shibbolethAttributes['supannEntiteAffectation']);
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

            $trainee->setService($shibbolethAttributes['amuAffectationLib']);
            $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
            //$trainee->setBap($shibbolethAttributes['amuBap']);
//            $trainee->setCampus($shibbolethAttributes['amuCampus']);
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
                $n_corps = $doctrine->getRepository('pp\Entity\Corps')->findOneBy(
                    array('corps' => $corps)
                );
                if ($n_corps != null) {
                    $trainee->setCorps($n_corps->getLibelleLong());
                    $trainee->setCategory($n_corps->getCategory());
                }
            }
        }
        else {
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
            $trainee->setAmuStatut($shibbolethAttributes['supannCodePopulation']);
            $corps = ltrim($shibbolethAttributes['supannEmpCorps'], "{NCORPS}");
            // Si on a une valeur, on cherche le libellé et la catégorie dans la table
            if (count($corps > 0)) {
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

        $form = $this->createForm(new ProfileType($this->get('sygefor_core.access_right_registry')), $trainee);

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // TEST sur le responsable
                if (count($trainee->getEmailSup())>0) {
                    // Vérification du mail qui doit être institutionnel
                    if (stripos($trainee->getEmailSup() , "@")>0) {
                        $domaine = substr($trainee->getEmailSup(), stripos($trainee->getEmailSup(), "@") + 1);
                        $listeDomaines = $this->getParameter('domaines');
                        // Association nom de domaine et établissement
                        if (array_key_exists($domaine, $listeDomaines)){
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de clui du stagiaire
                            if (strtolower($trainee->getEmailSup()) == strtolower($trainee->getEmail())) {
                                $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                parent::registerShibbolethTrainee($request, $trainee, true);
                                $em = $this->getDoctrine()->getManager();
                                $em->persist($trainee);
                                $em->flush();
                                $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');
//                                $this->get('security.token_storage')->getToken()->setUser($trainee);

                                //return $this->redirectToRoute('front.account');
                                return $this->redirectToRoute('front.public.myprogram');

                            }
                        }else {
                            $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                        /*
                        if (($domaine != "univ-amu.fr")&&($domaine != "univ-avignon.fr")&&($domaine != "univ-tln.fr")&&($domaine != "umontpellier.fr")&&($domaine != "univ-cotedazur.fr")&&($domaine != "unice.fr")&&($domaine != "centrale-marseille.fr")&&($domaine != "univ-lr.fr")&&($domaine != "univ-gustave-eiffel.fr")&&($domaine != "univ-reims.fr")&&($domaine != "univ-nantes.fr")&&($domaine != "insa-lyon.fr")) {
                            $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                        else {
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de clui du stagiaire
                            if ($trainee->getEmailSup() == $trainee->getEmail()) {
                                $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                parent::registerShibbolethTrainee($request, $trainee, true);
                                $em = $this->getDoctrine()->getManager();
                                $em->persist($trainee);
                                $em->flush();
                                $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');
                                $this->get('security.token_storage')->getToken()->setUser($trainee);

                                //return $this->redirectToRoute('front.account');
                                return $this->redirectToRoute('front.public.myprogram');
                            }
                        }*/
                    }
                } else {
                    parent::registerShibbolethTrainee($request, $trainee, true);
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($trainee);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');
//                    $this->get('security.token_storage')->getToken()->setUser($trainee);

                    //return $this->redirectToRoute('front.account');
                    return $this->redirectToRoute('front.public.myprogram');
                }
            }
        }

        return array('user' => $this->getUser(), 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName());
    }

}
