<?php

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Ivoz\Core\Domain\Service\DomainEventPublisher;
use Ivoz\Core\Application\RequestId;
use Ivoz\Core\Application\Event\CommandWasExecuted;

class MicroKernel extends Kernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
            new \Doctrine\Bundle\DoctrineCacheBundle\DoctrineCacheBundle(),
            new Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle(),
            new Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle(),

            /* Core */
            new Ivoz\CoreBundle\CoreBundle(),

            /* Provider */
            new Ivoz\ProviderBundle\ProviderBundle(),
        ];

        return $bundles;
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $loader->load(__DIR__ . '/../config/config.yml');
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        // kernel is a service that points to this class
        // optional 3rd argument is the route name
        $routes->add('/', 'kernel:processRtpRecordingAction');
    }

    public function processRtpRecordingAction()
    {
        $this->registerCommand();

        $encoder = $this->container->get(Recording\Encoder::class);
        $encoder->processAction();
        return new JsonResponse("Done!");
    }

    private function registerCommand()
    {
        /** @var DomainEventPublisher $eventPublisher */
        $eventPublisher = $this->container->get(
            DomainEventPublisher::class
        );

        /** @var RequestId $requestId */
        $requestId = $this->container->get(
            RequestId::class
        );

        $event = new CommandWasExecuted(
            $requestId->toString(),
            'Recordings',
            'processRtpRecording',
            [],
            []
        );

        $eventPublisher->publish($event);
    }

    // optional, to use the standard Symfony cache directory
    public function getCacheDir()
    {
        return __DIR__.'/../var/cache/'.$this->getEnvironment();
    }

    // optional, to use the standard Symfony logs directory
    public function getLogDir()
    {
        return __DIR__.'/../var/logs';
    }
}
