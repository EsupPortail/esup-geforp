<?php

namespace App\Controller\Front;

use App\Entity\Back\Trainee;
use App\Entity\Back\Institution;
use App\Entity\Term\Title;
use App\Entity\Term\Publictype;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route('/account')]
final class AccountController extends AbstractController
{
    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
        private readonly LoggerInterface $logger
    ) {
    }

    #[Route('/', name: 'front.account')]
    public function account(): RedirectResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            throw new AccessDeniedException('Vous devez être pleinement authentifié pour accéder à cette page.');
        }

        $user = $this->getUser();

        if (!$user) {
            throw new AccessDeniedException('Utilisateur non trouvé.');
        }

        $shibbolethAttributes = $user->getCredentials();
        $userEmail = $shibbolethAttributes['mail'] ?? null;
        $userPersistentId = $shibbolethAttributes['eppn'] ?? null;

        // Recherche du Trainee en base
        $trainee = null;
        $repository = $this->managerRegistry->getRepository(Trainee::class);

        if ($userPersistentId) {
            $trainee = $repository->findOneBy(['shibbolethPersistentId' => $userPersistentId]);
        }

        if (!$trainee && $userEmail) {
            $trainee = $repository->findOneBy(['email' => $userEmail]);

            // Si un Trainee existe avec cet email mais sans PersistentId, on l'associe
            if ($trainee && !$trainee->getShibbolethPersistentId()) {
                $trainee->setShibbolethPersistentId($userPersistentId);
            }
        }

        // Si aucun Trainee trouvé, on le crée
        if (!$trainee) {
            $trainee = new Trainee();
        }

        // Assignation des valeurs au Trainee
        $trainee->setTitle($this->managerRegistry->getRepository(Title::class)->findOneBy([
            'name' => $shibbolethAttributes['supannCivilite'] ?? 'M.'
        ]));
        $trainee->setLastName($shibbolethAttributes['sn'] ?? 'Inconnu');
        $trainee->setFirstName($shibbolethAttributes['givenName'] ?? 'Inconnu');
        $trainee->setEmail($userEmail);
        $trainee->setShibbolethPersistentId($userPersistentId);
        $trainee->setBirthDate(str_replace("-", "", (string) ($shibbolethAttributes['supannOIDCDateDeNaissance'] ?? '')));
        $trainee->setPhoneNumber($shibbolethAttributes['telephoneNumber'] ?? null);

        $this->logger->info('Trainee updated/created:', [
            'email' => $trainee->getEmail(),
            'last_name' => $trainee->getLastName(),
        ]);

        // 🔹 Gestion de l'affiliation
        $primaryAffiliation = strtolower($shibbolethAttributes['primary-affiliation'] ?? '');
        if ($primaryAffiliation === "staff") {
            $primaryAffiliation = "employee";
        }

        $publictype = $this->managerRegistry->getRepository(Publictype::class)->findOneBy([
            'machinename' => $primaryAffiliation
        ]);

        if (!$publictype && $primaryAffiliation === 'student') {
            $publictype = $this->managerRegistry->getRepository(Publictype::class)->findOneBy([
                'name' => 'enseignant'
            ]);
        }

        if (!$publictype) {
            $publictype = $this->managerRegistry->getRepository(Publictype::class)->findOneBy([
                'machinename' => 'other'
            ]);
        }

        $trainee->setPublictype($publictype);

        // 🔹 Gestion de l'établissement
        $flagEtab = false;
        $eppn = $userPersistentId ?? '';
        if (strpos($eppn, "@") !== false) {
            $domaine = substr($eppn, strpos($eppn, "@") + 1);
            $institutions = $this->managerRegistry->getRepository(Institution::class)->findAll();

            foreach ($institutions as $institution) {
                foreach ($institution->getDomains() as $domain) {
                    if (strtolower($domain->getName()) === strtolower($domaine)) {
                        $trainee->setInstitution($institution);
                        $flagEtab = true;
                        break 2;
                    }
                }
            }
        }

        if (!$flagEtab) {
            $this->addFlash('error', 'Votre établissement n\'a pas accès à la plate-forme.');
            return $this->redirectToRoute('front.public.index');
        }

        // Sauvegarde en base de données
        $entityManager = $this->managerRegistry->getManager();
        $entityManager->persist($trainee);
        $entityManager->flush();

        return $this->redirectToRoute('front.account');
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
        $arTrainee = $managerRegistry->getRepository(\App\Entity\Back\Trainee::class)->findByEmail($userEmail);
        $trainee = $arTrainee[0];

        $trainee->setShibbolethpersistentid($shibbolethAttributes['eppn']);
        // Gestion du cas où la civilité n'est pas renseignée : on met à M. par défaut
        if ($shibbolethAttributes['supannCivilite'] == '')
            $shibbolethAttributes['supannCivilite'] = 'M.';

        $trainee->setTitle($managerRegistry->getRepository(\App\Entity\Term\Title::class)->findOneBy(
            ['name' => $shibbolethAttributes['supannCivilite']]
        ));
        $trainee->setLastName($shibbolethAttributes['sn']);
        $trainee->setFirstName($shibbolethAttributes['givenName']);
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
            return $this->render('Front/Account/profile/profile.html.twig');
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
                                $request->getSession()->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail différente de la vôtre pour le responsable hiérarchique');
                            } else {
                                $em = $managerRegistry->getManager();
                                $em->flush();
                                $request->getSession()->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                            }
                        } else {
                            $request->getSession()->getFlashBag()->add('error', 'Vous devez rentrer une adresse mail INSTITUTIONNELLE pour le responsable hiérarchique');
                        }
                    }
                } else {
                    $em = $managerRegistry->getManager();
                    $em->flush();
                    $request->getSession()->getFlashBag()->add('success', 'Votre profil a été mis à jour.');
                }
            }
        }

        return ['user' => $trainee, 'form' => $form->createView(), 'disableAddress' => $adresseFromLdap, 'flagAMU' => $flagAMU, 'activeCorrForm' => $corrFormActif, 'etablissement' => $trainee->getInstitution()->getName()];
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
