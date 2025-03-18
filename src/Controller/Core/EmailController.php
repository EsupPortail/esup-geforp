<?php

/**
 * Created by PhpStorm.
 * User: Erwan
 * Date: 25/08/2015
 * Time: 12:30.
 */

namespace App\Controller\Core;

use App\Repository\EmailRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Core\Email;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class EmailController.
 *
 */
#[Route(path: '/email')]final class EmailController extends AbstractController
{
    // Recherche pour aggs et query_filters
    /**
     * @var mixed[]
     */
    private const TAB_AGGS = [];
    /**
     * @Rest\View(serializerGroups={"Default", "email"}, serializerEnableMaxDepthChecks=true)
     * @return array{total: int, pageSize: int, items: mixed, aggs: never[]}
     */
    #[Route(path: '/search', name: 'email.search', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function search(Request $request, ManagerRegistry $managerRegistry, EmailRepository $emailRepository): array
    {
        $keywords = $request->request->get('keywords', 'NO KEYWORDS');
        $filters = $request->request->all('filters','NO FILTERS');
        $request->request->get('query_filters', 'NO QUERY FILTERS');
        $request->request->all('aggs', 'NO AGGS');

        // Recherche avec les filtres
        $emails = $emailRepository->getEmailsList($keywords, $filters);
        $nbEmails  = is_countable($emails) ? count($emails) : 0;
        return ['total' => $nbEmails, 'pageSize' => 0, 'items' => $emails, 'aggs' => self::TAB_AGGS];
    }

    /**
     * @Rest\View(serializerGroups={"Default", "session", "user"}, serializerEnableMaxDepthChecks=true)
     * @return array{email: \App\Entity\Core\Email}
     */
    #[Route(path: '/view/{id}', requirements: ['id' => '\d+'], name: 'email.view', options: ['expose' => true], defaults: ['_format' => 'json'])]
    public function view(Email $email, ManagerRegistry $managerRegistry, int $id): array
    {
        $email = $managerRegistry->getRepository(Email::class)->find($id);
        if (!$email) {
            throw $this->createNotFoundException();
        }
        return ['email' => $email];
    }
}
