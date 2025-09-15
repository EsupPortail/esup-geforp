<?php

namespace App\Utils\Transformer;

use Doctrine\ORM\EntityManager;
use Elastica\Document;
use App\Entity\Core\AbstractSession;
use Symfony\Component\DependencyInjection\Container;

/**
 * Class SessionToElasticaTransformer.
 */
final class SessionToElasticaTransformer extends ModelToElasticaTransformer
{
    /**
     * @param array     $options
     */
    public function __construct(protected Container $container, $options = [])
    {
    }

    /**
     * @param AbstractSession $session
     *
     * @return Document
     */
    public function transform($session, array $fields): \Elastica\Document
    {
        $document = parent::transform($session, $fields);

        /*
         * Add inscriptionStats
         */
        if ($session instanceof AbstractSession) {
            /** @var EntityManager $em */
            $em = $this->container->get('doctrine')->getManager();
            $stats = [];
            if ($session->getRegistration() > AbstractSession::REGISTRATION_DEACTIVATED) {
                $query = $em
                  ->createQuery('SELECT s, count(i) FROM SygeforCoreBundle:Term\\InscriptionStatus s
                    JOIN SygeforCoreBundle:AbstractInscription i WITH i.inscriptionStatus = s
                    WHERE i.session = :session
                    GROUP BY s.id')
                  ->setParameter('session', $session);

                $result = $query->getResult();
                foreach ($result as $status) {
                    $stats[] = ['id' => $status[0]->getId(), 'name' => $status[0]->getName(), 'status' => $status[0]->getStatus(), 'count' => (int) $status[1]];
                }
            }

            $document->set('inscriptionStats', $stats);

            /*
             * HACK
             * ActivityReport : replace null by "Autre"
             */
            if ($session instanceof AbstractSession) {
                $stats = $document->get('participantsStats');
                foreach ($stats as $key => $stat) {
                    if (isset($stat['geographicOrigin']) && !$stat['geographicOrigin']) {
                        $stats[$key]['geographicOrigin'] = 'Autre';
                    }
                }

                $document->set('participantsStats', $stats);
            }
        }

        return $document;
    }
}
