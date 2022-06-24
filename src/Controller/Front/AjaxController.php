<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace App\Controller\Front;


use Doctrine\ORM\EntityManager;
use Sygefor\Bundle\ApiBundle\Controller\TrainingController;
use Sygefor\Bundle\FrontBundle\Form\SearchType;
use Sygefor\Bundle\MyCompanyBundle\Entity\Inscription;
use Sygefor\Bundle\FrontBundle\Form\InscriptionType;
use Sygefor\Bundle\TraineeBundle\Entity\AbstractTrainee;
use Sygefor\Bundle\TraineeBundle\Entity\Term\EmailTemplate;
use Sygefor\Bundle\MyCompanyBundle\Entity\Session;
use Sygefor\Bundle\CoreBundle\Entity\Organization;
use Sygefor\Bundle\TrainingBundle\Entity\Training\Term\Theme;
use Sygefor\Bundle\MyCompanyBundle\Entity\DateSession;
use Sygefor\Bundle\MyCompanyBundle\Entity\Internship;
use Sygefor\Bundle\TrainingBundle\Entity\Training\AbstractTraining;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Config\Definition\Exception\ForbiddenOverwriteException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

Use Elastica\Query;
Use Elastica\Filter\BoolAnd;
Use Elastica\Filter\BoolOr;
Use Elastica\Filter\Term;
Use Elastica\Filter\Range;
use Elastica\Query\Match;

class AjaxController extends AbstractController
{

    /**
     * Retourne la liste des sessions (autocomplétion)
     *
     * @Route("/ajax/completlist", name="ajax_completlist")
     *
     * @return string la liste des sessions au format json
     */
    public function CompletListAction()
    {
        $request = $this->get('request');

        $term = $request->request->get('motcle');
        $domaine = $request->request->get('domaine');
        $centre = $request->request->get('centre');

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();
        $theme = $em->getRepository('SygeforTrainingBundle:Training\Term\Theme')->findOneBy(array('id' => $domaine ));
        $organization = $em->getRepository('SygeforCoreBundle:Organization')->findOneBy(array('id' => $centre));

        if ($theme->getName() == "Tous les domaines") {
            $themeName = null;
        }
        else {
            $themeName = $theme->getName();
        }

        if ($organization->getCode() == "tous" ) {
            $code = null;
        }
        else {
            $code = $organization->getCode();
        }

        if (strlen($term)<3)
        {
            $json[] = array('label' => 'au moins 3 caractères ('.$term.')', 'value' => '');
            $response = new Response (json_encode($json));
            $response->headers->set('Content-Type','application/json');
            return $response;
        }

        // Recherche dans Elasticsearch
        $term = strtolower($term);
        $search = $this->createProgramQuerySearch(1, 100, $code, $themeName, $term);

        $arraySessions = array();

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
                $cpt++;
            }
        }

        $response = new Response (json_encode($arraySessions));
        $response->headers->set('Content-Type','application/json');
        return $response;

    }

    /**
     * @param $page
     * @param int $itemPerPage
     * @param $code
     * @param $theme
     * @return array
     */
    protected function createProgramQuerySearch($page, $itemPerPage = 10, $code = null, $theme = null, $texte = null)
    {
        $search = $this->get('sygefor_training.session.search');
        if ($page) {
            $search->setPage($page);
            $search->setSize($itemPerPage);
        }

        // add filters
        $filters = new BoolAnd();

        //centre
        if (!empty($code)) {
            $organization = new Term(array('training.organization.code' => $code));
            $filters->addFilter($organization);
        }

        // thème
        if (!empty($theme)) {
            $organization = new Term(array('training.theme.name' => $theme));
            $filters->addFilter($organization);
        }

        //texte
        if (!empty($texte)) {
            $name = new Term(array('training.name.autocomplete' => $texte));
            $filters->addFilter($name);
        }

        // date à venir
        $dateBegin = new Range('dateBegin', array("gte" => (new \DateTime("now", timezone_open('Europe/Paris')))->format('Y-m-d')));
        $filters->addFilter($dateBegin);

//        $types = new Terms('training.type', array('internship'));
//        $filters->addFilter($types);

        $search->addFilter('filters', $filters);

        $search->addSort('training.theme.name');
        $search->addSort('dateBegin');
        $search->addSort('training.name.source');

        return $search->search();
    }


}