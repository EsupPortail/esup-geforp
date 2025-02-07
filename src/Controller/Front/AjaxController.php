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

Use Elastica\Query;
Use Elastica\Filter\BoolAnd;
Use Elastica\Filter\BoolOr;
Use Elastica\Filter\Term;
Use Elastica\Filter\Range;

final class AjaxController extends AbstractController
{

    public function __construct(private readonly \Doctrine\Persistence\ManagerRegistry $managerRegistry)
    {
    }

    /**
     * Retourne la liste des sessions (autocomplétion)
     *
     *
     * @return string la liste des sessions au format json
     */
    #[Route(path: '/ajax/completlist', name: 'ajax_completlist')]
    public function CompletList(): \Symfony\Component\HttpFoundation\Response
    {
        $json = [];
        $request = $this->get('request');

        $term = $request->request->get('motcle');
        $domaine = $request->request->get('domaine');
        $centre = $request->request->get('centre');

        /** @var EntityManager $objectManager */
        $objectManager = $this->managerRegistry->getManager();
        $theme = $objectManager->getRepository('SygeforTrainingBundle:Training\Term\Theme')->findOneBy(['id' => $domaine]);
        $sygeforCoreBundle = $objectManager->getRepository('SygeforCoreBundle:Organization')->findOneBy(['id' => $centre]);

        $themeName = $theme->getName() == "Tous les domaines" ? null : $theme->getName();

        $code = $sygeforCoreBundle->getCode() == "tous" ? null : $sygeforCoreBundle->getCode();

        if (strlen((string) $term)<3)
        {
            $json[] = ['label' => 'au moins 3 caractères ('.$term.')', 'value' => ''];
            $response = new Response (json_encode($json));
            $response->headers->set('Content-Type','application/json');
            return $response;
        }

        // Recherche dans Elasticsearch
        $term = strtolower((string) $term);
        $search = $this->createProgramQuerySearch(1, 100, $code, $themeName, $term);

        $arraySessions = [];

        $NbEnreg = $search['total'];
        /*
        // si on a plus de 20 entrées, on affiche que le résultat partiel
        if ($NbEnreg>20)
            $arraySessions[0]['label']  = "... Résultat partiel ...";

        // on limite l'affichage à 20 groupes
        ($NbEnreg>20) ? $NbEnreg=20 : $NbEnreg;
        */
        if ($NbEnreg == 0) {
            $arraySessions[0]['label'] = 'Pas de résultat';

        } else {
            $cpt = 1;
            foreach ($search['items'] as $res)
            {
                $arraySessions[$cpt]['label']  = $res['name'];
                ++$cpt;
            }
        }

        $response = new Response (json_encode($arraySessions, JSON_THROW_ON_ERROR));
        $response->headers->set('Content-Type','application/json');
        return $response;

    }

    /**
     * @param $page
     * @param $code
     * @param $theme
     * @return array
     */
    private function createProgramQuerySearch(int $page, int $itemPerPage = 10, $code = null, $theme = null, $texte = null)
    {
        $search = $this->get('sygefor_training.session.search');
        if ($page !== 0) {
            $search->setPage($page);
            $search->setSize($itemPerPage);
        }

        // add filters
        $boolAnd = new BoolAnd();

        //centre
        if (!empty($code)) {
            $organization = new Term(['training.organization.code' => $code]);
            $boolAnd->addFilter($organization);
        }

        // thème
        if (!empty($theme)) {
            $organization = new Term(['training.theme.name' => $theme]);
            $boolAnd->addFilter($organization);
        }

        //texte
        if (!empty($texte)) {
            $name = new Term(['training.name.autocomplete' => $texte]);
            $boolAnd->addFilter($name);
        }

        // date à venir
        $range = new Range('dateBegin', ["gte" => (new \DateTime("now", timezone_open('Europe/Paris')))->format('Y-m-d')]);
        $boolAnd->addFilter($range);

//        $types = new Terms('training.type', array('internship'));
//        $filters->addFilter($types);

        $search->addFilter('filters', $boolAnd);

        $search->addSort('training.theme.name');
        $search->addSort('dateBegin');
        $search->addSort('training.name.source');

        return $search->search();
    }


}