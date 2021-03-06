<?php

namespace Ivoz\Provider\Domain\Service\Ddi;

use Ivoz\Core\Application\Service\EntityTools;
use Ivoz\Provider\Domain\Model\Company\CompanyInterface;
use Ivoz\Provider\Domain\Model\Country\CountryRepository;
use Ivoz\Provider\Domain\Model\Ddi\DdiDto;
use Ivoz\Provider\Domain\Model\Ddi\DdiInterface;
use Ivoz\Provider\Domain\Model\Ddi\DdiRepository;
use Ivoz\Provider\Domain\Model\DdiProvider\DdiProviderRepository;

class DdiFactory
{
    protected $countryRepository;
    protected $ddiRepository;
    protected $ddiProviderRepository;
    protected $entityTools;

    public function __construct(
        CountryRepository $countryRepository,
        DdiRepository $ddiRepository,
        DdiProviderRepository $ddiProviderRepository,
        EntityTools $entityTools
    ) {
        $this->ddiRepository = $ddiRepository;
        $this->countryRepository = $countryRepository;
        $this->ddiProviderRepository = $ddiProviderRepository;
        $this->entityTools = $entityTools;
    }

    /**
     * @throws \Exception
     */
    public function fromMassProvisioningCsv(
        CompanyInterface $company,
        string $countryCode,
        string $ddiNumber,
        string $ddiProviderName
    ): DdiInterface {

        $country = $this->countryRepository->findOneByCode(
            $countryCode
        );

        if (!$country) {
            throw new \DomainException(
                'country not found',
                404
            );
        }

        $ddiProvider = null;
        if ($ddiProviderName) {
            $ddiProvider = $this
                ->ddiProviderRepository
                ->findOneByBrandAndName(
                    $company->getBrand()->getId(),
                    $ddiProviderName
                );

            if (!$ddiProvider) {
                throw new \DomainException(
                    'DDI provider not found',
                    404
                );
            }
        }

        $ddi = $this
            ->ddiRepository
            ->findOneByDdiAndCountry(
                $ddiNumber,
                $country->getId()
            );

        if ($ddi) {
            return $ddi;
        }

        $ddiDto = new DdiDto();
        $ddiDto
            ->setDdi($ddiNumber)
            ->setCountryId(
                $country->getId()
            )
            ->setCompanyId(
                $company->getId()
            )
            ->setBrandId(
                $company->getBrand()->getId()
            );

        if ($ddiProvider) {
            $ddiDto->setDdiProviderId(
                $ddiProvider->getId()
            );
        }

        $ddi = $this->entityTools->dtoToEntity(
            $ddiDto
        );

        return $ddi;
    }
}
