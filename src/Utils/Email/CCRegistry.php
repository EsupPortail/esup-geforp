<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 8/1/17
 * Time: 4:11 PM.
 */

namespace App\Utils\Email;

/**
 * Class CCRegistry.
 */
final class CCRegistry
{
    /**
     * @param mixed[] $resolvers
     */
    public function __construct(private $resolvers)
    {
    }

    /**
     * @param $entity
     *
     * @return array<mixed, array{name: string, checked: bool}>
     */
    public function getSupportedResolvers($entity = null): array
    {
        $resolvers = [];
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
