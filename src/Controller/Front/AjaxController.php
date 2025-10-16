<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller\Front;


use Doctrine\ORM\EntityManager;
use App\Controller\TrainingController;
use App\Form\Type\SearchType;

use App\Entity\Back\Inscription;
use App\Form\Type\InscriptionType;
use App\Entity\Core\AbstractTrainee;
use App\Entity\Term\EmailTemplate;
use App\Entity\Back\Session;
use App\Entity\Back\Organization;
use App\Entity\Term\Theme;
use App\Entity\Back\DateSession;
use App\Entity\Back\Internship;
use App\Entity\Core\AbstractTraining;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;


Use Elastica\Query;
Use Elastica\Filter\BoolAnd;
Use Elastica\Filter\BoolOr;
Use Elastica\Filter\Term;
Use Elastica\Filter\Range;
use Elastica\Query\Prefix;

final class AjaxController extends AbstractController
{

    public function __construct(
        private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry,
        private readonly \App\Repository\SessionRepository $sessionRepository
    ) {}


    /**
     * Retourne la liste des sessions (autocomplétion)
     *
     *
     * @return string la liste des sessions au format json
     */
    #[Route(path: '/ajax/completlist', name: 'ajax_completlist')]
    public function CompletList(Request $request): JsonResponse
    {
        try{
        $term = $request->request->get('motcle');
        $domaine = $request->request->get('domaine');
        $centre = $request->request->get('centre');
        // error_log("motcle: " . $term . ", domaine: " . $domaine . ", centre: " . $centre);

        /** @var EntityManager $objectManager */
        $objectManager = $this->managerRegistry->getManager();
        $theme = $objectManager->getRepository(Theme::class)->findOneBy(['id' => $domaine]);
        $sygeforCoreBundle = $objectManager->getRepository(Organization::class)->findOneBy(['id' => $centre]);

        //error_log("theme: " . ($theme ? $theme->getName() : 'null'));
        // error_log("code: " . ($sygeforCoreBundle ? $sygeforCoreBundle->getCode() : 'null'));

        $themeName = $theme ? ($theme->getName() == "Tous les domaines" ? null : $theme->getName()) : null;
        $code = $sygeforCoreBundle ? ($sygeforCoreBundle->getCode() == "tous" ? null : $sygeforCoreBundle->getCode()) : null;

        if (strlen((string) $term) < 3) {
            return new JsonResponse([
                ['label' => 'au moins 2 caractères ('.$term.')', 'value' => '']
            ]);
        }

            $filters = [];
            if ($code) {
                $filters['training.organization.name.source'] = [$code];
            }
            if ($themeName) {
                $filters['theme.name'] = [$themeName];
            }
            $fields = ['id', 'name'];
            $page = 1;
            $pageSize = 100;
            $sorts = ['datebegin' => 'ASC'];
            $filters['datebegin'] = (new \DateTime())->format('d/m/Y') . ' - 31/12/2100';
            $search = $this->sessionRepository->getSessionsList($term, $filters, $page, $pageSize, $sorts, $fields);

            $arraySessions = [];
            $NbEnreg = $search['total'] ?? 0;

            if ($NbEnreg == 0) {
                $arraySessions[] = ['label' => 'Pas de résultat', 'value' => ''];
            } else {
                foreach ($search['items'] as $res) {
                    $arraySessions[] = [
                        'label' => $res['name'],
                        'value' => $res['name']
                    ];
                }
            }

            return new JsonResponse($arraySessions);

        } catch (\Throwable $e) {
            return new JsonResponse([
                ['label' => 'Erreur de recherche: ' . $e->getMessage(), 'value' => '']
            ], 500);
        }
    }

}