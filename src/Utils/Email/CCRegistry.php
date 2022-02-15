<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/1/17
 * Time: 4:11 PM.
 */

namespace CoreBundle\Utils\Email;

/**
 * Class CCRegistry.
 */
class CCRegistry
{
    /**
     * @var array
     */
    private $resolvers;

    public function __construct($resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @param $entity
     *
     * @return array
     */
    public function getSupportedResolvers($entity = null)
    {
        $resolvers = array();
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            if (!$entity || $resolver::supports($entity)) {
                $resolvers[$resolver] = [
                    'name' => $resolver::getName(),
                    'checked' => $resolver::checkedByDefault(),
                ];
            }
        }

        return $resolvers;
    }

    /**
     * @param string $resolverName
     * @param $entity
     *
     * @return mixed
     */
    public function resolveName($resolverName, $entity)
    {
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            if ($resolver::getName() === $resolverName) {
                return $resolver::resolveName($entity);
            }
        }

        return null;
    }

    /**
     * @param string $resolverName
     * @param $entity
     *
     * @return mixed
     */
    public function resolveEmail($resolverName, $entity)
    {
        /** @var EmailResolverInterface $resolver */
        foreach ($this->resolvers as $resolver) {
            if ($resolver::getName() === $resolverName) {
                return $resolver::resolveEmail($entity);
            }
        }

        return null;
    }
}
