<?php

/**
 * Created by PhpStorm.
 * User: maxime
 * Date: 10/07/14
 * Time: 15:23.
 */

namespace App\Controller\Core;

use App\Entity\Back\Presence;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\AbstractSession;
use App\Entity\Core\Material;
use App\Entity\Core\AbstractTraining;
use App\Form\Type\MaterialType;
use App\Entity\Back\FileMaterial;
use App\Entity\Back\LinkMaterial;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Exception\Exception;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

#[Route(path: '/material')]
abstract class AbstractMaterialController extends AbstractController
{
    /**
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     * @throws \Exception
     */
    #[Rest\View(serializerEnableMaxDepthChecks: true)]
    #[Route(path: '/{entity_id}/add/{type_entity}/{material_type}/', name: 'material.add', options: ['expose' => true], defaults: ['_format' => 'json', 'material_type' => 'file'])]
    public function add($entity_id, $type_entity, $material_type, Request $request, ManagerRegistry $managerRegistry): array
    {
        $form = null;
        $entity = null;
        /*
        $trainingTypes = $this->get('sygefor_training.type.registry')->getTypes();

        foreach ($trainingTypes as $type => $infos) {
            if ($type_entity === str_replace('_', '', $type)) {
                $entity = $this->getDoctrine()->getRepository($infos['class'])->find($entity_id);
                break;
            }
        }*/

        if (!$entity && $type_entity === 'session') {
            $entity = $managerRegistry->getRepository(\App\Entity\Core\AbstractSession::class)->find($entity_id);
        }

        if (!$entity instanceof \App\Entity\Core\AbstractSession) {
            throw new \Exception($type_entity . ' is not managed for materials');
        }

//        if (!$this->get('security.context')->isGranted('EDIT', $entity)) {
//            throw new AccessDeniedException('Accès non autorisé');
//        }

        $setEntityMethod = $type_entity === 'session' ? 'setSession' : 'setTraining';

        // a file is sent : creating a file material
        if ($material_type === 'file') {
            $material = new FileMaterial();
            $material->$setEntityMethod($entity);
            $form = $this->createForm(MaterialType::class, $material);
            if ($request->getMethod() === 'POST') {
                $form->handleRequest($request);

                if ($request->files->count() !== 0) {
                    foreach ($request->files as $file) {
                        //we have to test it in another
                        if ($file[0]->getSize() <= FileMaterial::getMaxFileSize()) {
                            $material = new FileMaterial();
                            $material->$setEntityMethod($entity);
                            $material->setFile($file[0]);

                            $em = $managerRegistry->getManager();

                            //persisting material calls move method on file, that can throw an exception if file size limit
                            //is too small in server config
                            try {
                                $em->persist($material);
                            }
                            catch (FileException) {
                                return ['error' => "Le fichier n'a pu être téléchargé"];
                            }

                            $em->flush();
                        }
                        else {
                            return ['error' => 'Le fichier ' . $file[0]->getClientOriginalName() . ' est trop volumineux'];
                        }
                    }

                    return ['material' => $material];
                }
                //files could be stripped by web server (eg by php.ini's limitations) : we can't get any infos about it
                return ['error' => "Le fichier n'a pu être téléchargé"];
            }
        } elseif ($material_type === 'link') {
            // no file sent : a link material is sent
            $material = new LinkMaterial();
            $material->$setEntityMethod($entity);
            $form = $this->createFormBuilder($material)
                ->add('name', null, ['label' => 'Nom', 'required' => 'true'])
                ->add('url', null, ['label' => 'Lien'])
                ->getForm();
            if ($request->getMethod() === 'POST') {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $material->$setEntityMethod($entity);

                    $em = $managerRegistry->getManager();

                    $em->persist($material);
                    $em->flush();

                    return ['material' => $material];
                }
            }
        }

        return ['form' => $form->createView()];
    }

    /**
     * @Rest\View
     */
    #[Rest\View()]
    #[Route(path: '/{id}/remove/', name: 'material.remove', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function delete(Material $material, ManagerRegistry $managerRegistry, int $id): array
    {
        $material = $managerRegistry->getRepository(Material::class)->find($id);
        if (!$material) {
            throw $this->createNotFoundException();
        }
//        if (($material->getTraining() && $this->get('security.context')->isGranted('EDIT', $material->getTraining())) ||
//            ($material->getSession() && $this->get('security.context')->isGranted('EDIT', $material->getSession()))) {
        /** @var $em */
        $objectManager = $managerRegistry->getManager();
        try {
            $objectManager->remove($material);
            $objectManager->flush();
        }
        catch (\Exception $exception) {
            return ['error' => $exception->getMessage()];
        }

        return [];
//        }
//        else {
//            throw new AccessDeniedException('Accès non autorisé');
//        }
    }

    /**
     * @Rest\View
     */
    #[Rest\View()]
    #[Route(path: '/{id}/get/', name: 'material.get', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function getAction(ManagerRegistry $managerRegistry, int $id)
    {
        $material = $managerRegistry->getRepository(Material::class)->find($id);
        if (!$material) {
            throw $this->createNotFoundException();
        }
        if ($material->getType() === 'file') {
            return $material->send();
        }
        if ($material->getType() === 'link') {
            return $material->getUrl();
        }
        return $material;
    }

    /**
     * @param $entity_id
     * @param $entity_type
     *
     * @return AbstractTraining|AbstractSession
     *
     * @throws
     */
    protected function getEntity($entity_id, $entity_type, ManagerRegistry $managerRegistry): AbstractTraining|AbstractSession
    {
        $entity = null;
/*        $trainingTypes = $this->get('sygefor_core.registry.training_type')->getTypes();
        foreach ($trainingTypes as $type => $infos) {
            if ($entity_type === str_replace('_', '', $type)) {
                $entity = $doctrine->getRepository($infos['class'])->find($entity_id);
                break;
            }
        }*/

        if (!$entity && $entity_type === 'session') {
            $entity = $managerRegistry->getRepository(AbstractSession::class)->find($entity_id);
        }

        if (!$entity instanceof \App\Entity\Core\AbstractSession) {
            throw \Exception($entity_type.' is not managed for materials');
        }

/*        if (!$this->get('security.context')->isGranted('EDIT', $entity)) {
            throw new AccessDeniedException('Accès non autorisé');
        }*/

        return $entity;
    }
}
