<?php

namespace spec\Ivoz\Provider\Domain\Model\Ddi;

use Ivoz\Provider\Domain\Model\Brand\Brand;
use Ivoz\Provider\Domain\Model\Company\Company;
use Ivoz\Provider\Domain\Model\Country\CountryInterface;
use Ivoz\Provider\Domain\Model\Ddi\Ddi;
use Ivoz\Provider\Domain\Model\Ddi\DdiDto;
use Ivoz\Provider\Domain\Model\DdiProvider\DdiProviderInterface;
use Ivoz\Provider\Domain\Model\PeeringContract\PeeringContractInterface;
use PhpSpec\ObjectBehavior;
use spec\HelperTrait;

class DdiSpec extends ObjectBehavior
{
    use HelperTrait;

    /**
     * @var DdiDto
     */
    protected $dto;

    /**
     * @var DdiProviderInterface
     */
    protected $ddiProvider;

    function let(
        CountryInterface $country,
        DdiProviderInterface $ddiProvider
    ) {
        $this->ddiProvider = $ddiProvider;

        $this->dto = $dto = new DdiDto();
        $dto
            ->setDdi('123')
            ->setRecordCalls('none')
            ->setBillInboundCalls(0);

        $brand = $this->getInstance(
            Brand::class
        );

        $company = $this->getInstance(
            Company::class,
            ['brand' => $brand]
        );

        $this->hydrate(
            $dto,
            [
                'country' => $country->getWrappedObject(),
                'ddiProvider' => $ddiProvider->getWrappedObject(),
                'brand' =>  $brand,
                'company' => $company,
            ]
        );

        $country
            ->getCountryCode()
            ->willReturn('34');

        $country
            ->getId()
            ->willReturn(1);

        $this->beConstructedThrough(
            'fromDto',
            [$dto, new \spec\DtoToEntityFakeTransformer()]
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(Ddi::class);
    }

    function it_throws_exception_on_invalid_ddi()
    {
        $this
            ->shouldThrow('\Exception')
            ->during('setDdi', ['$123']);

        $this
            ->shouldThrow('\Exception')
            ->during('setDdi', ['94 600 20 55']);
    }

    function it_accepts_valid_ddis()
    {
        $this
            ->shouldNotThrow('Exception')
            ->during('setDdi', ['946002055']);

        $this
            ->shouldNotThrow('Exception')
            ->during('setDdi', ['112']);

        $this
            ->shouldNotThrow('Exception')
            ->during('setDdi', ['999']);
    }

    function it_sets_standarized_number()
    {
        $this
            ->getDdie164()
            ->shouldBe('34123');
    }
}
