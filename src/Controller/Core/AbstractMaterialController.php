<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 10/07/14
 * Time: 15:23.
 */

namespace App\Controller\Core;

use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\AbstractMaterial;
use App\Entity\Core\AbstractTraining;
use App\Form\Type\AbstractMaterialType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/material")
 */
abstract class AbstractMaterialController extends AbstractController
{
    /**
     * @Route("/{entity_id}/add/{entity_type}/{material_type}/{isPublic}", name="material.add", options={"expose"=true}, defaults={"_format" = "json", "isPublic": false})
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    public function addAction(Request $request, $entity_id, $entity_type, $material_type, $isPublic = false)
    {
        $entity = $this->getEntity($entity_id, $entity_type);
        $setEntityMethod = get_parent_class($entity) instanceof AbstractSession ? 'setSession' : 'setTraining';
        $materialClass = AbstractMaterial::class;
        $material = new $materialClass($isPublic);
        $entity->addMaterial($material);
        $material->$setEntityMethod($entity);

        //        switch ($material_type) {
        //            case 'type':
        //                $material = new MyMaterial($isPublic);
        //                $form = $this->createForm(MyMaterialType::class, $material);
        //                break;
        //            default:
        //                break;
        //        }
        //        $material->$setEntityMethod($entity);

        $form = $this->createForm(AbstractMaterialType::class, $material);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($material);
            $em->flush();

            return array('material' => $material);
        }

        return array('form' => $form->createView());
    }

    /**
     * @Route("/{id}/remove/", name="material.remove", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     * @ParamConverter("material", class="SygeforCoreBundle:AbstractMaterial", options={"id" = "id"})
     */
    public function deleteAction(AbstractMaterial $material)
    {
        /** @var $em */
        $em = $this->getDoctrine()->getManager();
        try {
            $entity = $material->getTraining() ? $material->getTraining() : $material->getSession();
            $entity->removeMaterial($material);
            $em->remove($material);
            $em->flush();
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }

        return array();
    }

    /**
     * @Route("/{id}/get/", name="material.get", options={"expose"=true}, defaults={"_format" = "json"})
     * @Rest\View
     * @ParamConverter("material", class="SygeforCoreBundle:AbstractMaterial", options={"id" = "id"})
     */
    public function getAction($material)
    {
        if ($material->getType() === 'file') {
            return $material->send();
        } elseif ($material->getType() === 'link') {
            return $material->getUrl();
        }
    }

    /**
     * @param $entity_id
     * @param $entity_type
     *
     * @return AbstractTraining|AbstractSession
     *
     * @throws
     */
    protected function getEntity($entity_id, $entity_type)
    {
        $entity = null;
        $trainingTypes = $this->get('sygefor_core.registry.training_type')->getTypes();
        foreach ($trainingTypes as $type => $infos) {
            if ($entity_type === str_replace('_', '', $type)) {
                $entity = $this->getDoctrine()->getRepository($infos['class'])->find($entity_id);
                break;
            }
        }

        if (!$entity && $entity_type === 'session') {
            $entity = $this->getDoctrine()->getRepository(AbstractSession::class)->find($entity_id);
        }

        if (!$entity) {
            throw \Exception($entity_type.' is not managed for materials');
        }

        if (!$this->get('security.context')->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException('Accès non autorisé');
        }

        return $entity;
    }
}
