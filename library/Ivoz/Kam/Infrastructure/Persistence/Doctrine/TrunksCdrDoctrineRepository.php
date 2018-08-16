<?php

namespace Ivoz\Kam\Infrastructure\Persistence\Doctrine;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Ivoz\Kam\Domain\Model\TrunksCdr\TrunksCdrRepository;
use Ivoz\Kam\Domain\Model\TrunksCdr\TrunksCdr;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Model\Helper\CriteriaHelper;
use Ivoz\Core\Infrastructure\Persistence\Doctrine\Traits\GetGeneratorByConditionsTrait;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * TrunksCdrDoctrineRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class TrunksCdrDoctrineRepository extends ServiceEntityRepository implements TrunksCdrRepository
{
    use GetGeneratorByConditionsTrait;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, TrunksCdr::class);
    }

    /**
     * This method expects results to be marked as metered as soon as they're used:
     * a.k.a it does not apply any query offset, just a limit
     *
     * @inheritdoc
     * @see TrunksCdrRepository::getUnmeteredCallsGeneratorWithoutOffset
     */
    public function getUnmeteredCallsGeneratorWithoutOffset(int $batchSize, array $order = null)
    {
        $dateFrom = new \DateTime(
            '10 seconds ago',
            new \DateTimeZone('UTC')
        );

        /**
         * @var \Doctrine\ORM\EntityRepository $this
         */
        $qb = $this->createQueryBuilder('self');
        $qb->addCriteria(CriteriaHelper::fromArray([
            ['metered', 'eq', '0'],
            ['direction', 'eq', 'outbound'],
            ['endTime', 'lte', $dateFrom->format('Y-m-d H:i:s')],
        ]));
        $qb->setMaxResults($batchSize);

        if ($order) {
            $qb->orderBy(...$order);
        }

        $currentPage = 1;
        $continue =  true;
        while ($continue) {

            $query = $qb->getQuery();
            $results = $query->getResult();
            $continue = count($results) === $batchSize;
            $currentPage++;

            yield $results;
        }
    }
}
