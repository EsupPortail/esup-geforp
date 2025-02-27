<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 9/15/16
 * Time: 10:43 AM
 */

namespace App\Controller\Front;


use App\AccessRight\AccessRightRegistry;
use App\Security\LogInFormAuthenticator;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;
use App\Form\Type\ProfileType;
use App\Entity\Back\Trainee;
use App\Entity\Back\SupannCodeEntite;
use mysql_xdevapi\Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

/**
 * This controller regroup all public actions relative to account.
 *
 */
#[Route(path: '/account')]final class AnonymousAccountController extends AbstractController
{
    private const string TRAINEE_CLASS = Trainee::class;
    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }


    /**
     * Register a new account with data.
     *
     */
    #[Route(path: '/register', name: 'front.account.register')]
    public function register(Request $request, ManagerRegistry $managerRegistry, AccessRightRegistry $accessRightRegistry): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            // Si l'utilisateur n'est pas authentifié pleinement, on redirige ou on lève une exception
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }
        $trainee = new Trainee();

        // Recuperation paramétrage des champs du formulaire
        $adresseFromLdap = $this->getParameter('adresse_from_ldap');
        $corrFormActif = $this->getParameter('corresp_form_actif');

        $shibbolethAttributes = $this->getUser()->getCredentials();

        if (!is_array($shibbolethAttributes)) {
            throw new AuthenticationException('Les attributs Shibboleth ne sont pas un tableau.');
        }

        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite']=='')
            $shibbolethAttributes['supannCivilite'] = 'M.';

        $trainee->setTitle($managerRegistry->getRepository(\App\Entity\Term\Title::class)->findOneBy(
            ['name' => $shibbolethAttributes['supannCivilite']]
        ));
        $trainee->setLastname($shibbolethAttributes['sn']);
        $trainee->setFirstname($shibbolethAttributes['givenName']);
        $trainee->setEmail($shibbolethAttributes['mail']);

        $datenaiss = str_replace("-", "", (string) $shibbolethAttributes['supannOIDCDateDeNaissance']);
        $trainee->setBirthdate($datenaiss);
        // Mise en forme adresse au cas où il y en a une
        if ($adresseFromLdap && ($shibbolethAttributes['postalAddress'] != "")) {
            $address = $shibbolethAttributes['postalAddress'];
            // Recupération du code postal
            preg_match('#\$\d{5}#', (string) $address, $result, PREG_OFFSET_CAPTURE, 3);
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

        $trainee->setPhonenumber($shibbolethAttributes['telephoneNumber']);
        if ($shibbolethAttributes['primary-affiliation'] == "staff") {
            // Transformation de l'attribut 'staff' en 'employee'
            $shibbolethAttributes['primary-affiliation'] = "employee";
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
                } elseif (str_contains((string) $tabCursus, 'D')) {
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
                $this->get('session')->getFlashBag()->add('error', 'Vous ne pouvez pas vous inscrire sur Geforp. La plate-forme n\'est pas accessible aux étudiants.');
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
            $trainee->setAmustatut($shibbolethAttributes['supannCodePopulation']);

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

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);
            if ($form->isValid()) {
                // TEST sur le responsable
                if ($trainee->getEmailsup() !== '' && $trainee->getEmailsup() !== '0') {
                    // Vérification du mail qui doit être institutionnel
                    if (stripos($trainee->getEmailsup() , "@")>0) {
                        $domaine = substr($trainee->getEmailsup(), stripos($trainee->getEmailsup(), "@") + 1);
                        $domaines = $trainee->getInstitution()->getDomains();
                        $listeDomaines = [];
                        foreach ($domaines as $dom) {
                            $listeDomaines[$dom->getName()] = $dom;
                        }

                        // Association nom de domaine et établissement
                        if (array_key_exists($domaine, $listeDomaines)){
                            // ok : c'est bien une adresse institutionnelle qui a été renseignée
                            // Mail institutionel ok
                            // on vérifie que le mail du responsable est différent de celui du stagiaire
                            if (strtolower($trainee->getEmailsup()) === strtolower((string) $trainee->getEmail())) {
                                $this->get('session')->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $this->registerShibbolethTrainee($this->getUser()->getCredentials(), $trainee);
                                $trainee->setCreatedAt(new \DateTime('now'));
                                $trainee->setUpdatedAt(new \DateTime('now'));

                                $em = $managerRegistry->getManager();
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
                    $this->registerShibbolethTrainee($this->getUser()->getCredentials(), $trainee);
                    $trainee->setCreatedAt(new \DateTime('now'));
                    $trainee->setUpdatedAt(new \DateTime('now'));

                    $em = $managerRegistry->getManager();
                    $em->persist($trainee);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', 'Votre profil a bien été créé.');

                    return $this->render('Front/Account/profile/account-registration.html.twig');
                }
            }
        }
        return ['user' => $this->getUser(), 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName()];
    }

    /**
     * @param $cred
     * @param $trainee
     * @param boolean
     */
    private function registerShibbolethTrainee($cred, \App\Entity\Back\Trainee $trainee): void
    {
        $trainee->setIsActive(false);

        if (true) {
            $email        = $cred['mail'];
            $eppn = $cred['eppn'];
            $trainee->setShibbolethpersistentid($eppn ?: $email);
            $trainee->setEmail($email);
            $trainee->setIsActive(true);
        }

    }

}
