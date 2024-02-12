<?php
namespace BlocksEdit\Controller;

use BlocksEdit\Cache\CacheTrait;
use BlocksEdit\Html\FlasherInterface;
use BlocksEdit\Html\NonceGeneratorInterface;
use BlocksEdit\Http\Exception\BadRequestException;
use BlocksEdit\Http\Exception\NotFoundException;
use BlocksEdit\Http\Exception\UnauthorizedException;
use BlocksEdit\Http\JsonResponse;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use BlocksEdit\Http\Response;
use BlocksEdit\Http\RouteGeneratorInterface;
use BlocksEdit\Http\StatusCodes;
use BlocksEdit\IO\FilesTrait;
use BlocksEdit\IO\Paths;
use BlocksEdit\IO\PathsTrait;
use BlocksEdit\IO\Files;
use BlocksEdit\Config\Config;
use BlocksEdit\System\Serializer;
use BlocksEdit\Twig\TwigRender;
use Entity\BillingPlan;
use Entity\CreditCard;
use Entity\User;
use Exception;
use Psr\Log\LoggerInterface;
use Redis;
use Repository\AccessRepository;
use Repository\NotificationRepository;
use Repository\OrganizationAccessRepository;
use Repository\UserRepository;
use Service\AuthService;
use Repository\BillingPlanRepository;
use Repository\CreditCardRepository;
use Repository\OrganizationsRepository;
use Service\Mentions;
use Symfony\Component\DependencyInjection\Container;
use Psr\Container\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Class Controller
 */
abstract class Controller
{
    use PathsTrait;
    use FilesTrait;
    use CacheTrait;

    /**
     * @var ContainerInterface|Container
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FlasherInterface
     */
    protected $flash;

    /**
     * @var RouteGeneratorInterface
     */
    protected $routeGenerator;

    /**
     * @var NonceGeneratorInterface
     */
    protected $nonce;

    /**
     * @var FormFactoryInterface
     */
    protected $formBuilder;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     *
     * @throws Exception
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container      = $container;
        $this->config         = $container->get(Config::class);
        $this->logger         = $container->get(LoggerInterface::class);
        $this->flash          = $container->get(FlasherInterface::class);
        $this->nonce          = $container->get(NonceGeneratorInterface::class);
        $this->routeGenerator = $container->get(RouteGeneratorInterface::class);
        $this->formBuilder    = $container->get(FormFactoryInterface::class);
        $this->setFiles($container->get(Files::class));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getUser(): array
    {
        $uid = $this->container->get(AuthService::class)->getLoginId();
        if ($uid) {
            return $this->container->get(UserRepository::class)->findByID($uid);
        }

        return [];
    }

    /**
     * @return BillingPlan
     * @throws Exception
     */
    public function getBillingPlan(): BillingPlan
    {
        $oid  = $this->container->get(Request::class)->getOrgSubdomain();
        $plan = $this->container->get(BillingPlanRepository::class)->findByOrg($oid);
        if (!$plan) {
            $plan = (new BillingPlan())
                ->setType('solo')
                ->setOrgId($oid)
                ->setChargeDay((int)date('d'));
        }

        return $plan;
    }

    /**
     * @param User $user
     * @param int  $oid
     *
     * @return array
     * @throws Exception
     */
    public function getFrontendBillingPlan(User $user, int $oid): array
    {
        $billingPlan = $this->container->get(Serializer::class)->serializeBillingPlan($this->getBillingPlan(), $user);
        $billingPlan['hasCreditCard'] = false;
        if ($this->getBillingCreditCard()) {
            $billingPlan['hasCreditCard'] = true;
        }

        $count = $this->container->get(AccessRepository::class)->findUserCount($oid);
        $billingPlan['hasTeamMembers'] = $count > 1;

        $initialOwner   = $this->container->get(OrganizationAccessRepository::class)->findInitialOwner($oid);
        $isInitialOwner = $user->getId() == $initialOwner['usr_id'];
        $billingPlan['ownerEmail']  = $initialOwner['usr_email'];
        $billingPlan['canTeamEdit'] = true;
        if (!$isInitialOwner && $this->getBillingPlan()->isSolo() && $this->getBillingPlan()->isTrialComplete()) {
            $billingPlan['canTeamEdit'] = false;
        }
        if (!$isInitialOwner && $this->getBillingPlan()->isPaused()) {
            $billingPlan['canTeamEdit'] = false;
        }

        return $billingPlan;
    }

    /**
     * @return CreditCard|null
     * @throws Exception
     */
    public function getBillingCreditCard(): ?CreditCard
    {
        $oid = $this->container->get(Request::class)->getOrgSubdomain();

        return $this->container->get(CreditCardRepository::class)->findActiveCard($oid);
    }

    /**
     * @param string $path
     * @param array  $vars
     *
     * @return Response
     * @throws Exception
     */
    public function render(string $path, array $vars = []): Response
    {
        $user = $this->getUser();
        $twig = $this->container->get(TwigRender::class);
        $html = $twig->render($path, $vars, $user['usr_timezone'] ?? 'America/New_York');

        return new Response($html);
    }

    /**
     * @param mixed $content
     * @param int   $statusCode
     * @param array $headers
     * @param bool  $isString
     *
     * @return JsonResponse
     */
    public function json(
        $content,
        int $statusCode = StatusCodes::OK,
        array $headers = [],
        bool $isString = false
    ): JsonResponse {
        return new JsonResponse($content, $statusCode, $headers, $isString);
    }

    /**
     * @param string $url
     *
     * @return RedirectResponse
     */
    public function redirect(string $url): RedirectResponse
    {
        return new RedirectResponse($url);
    }

    /**
     * @param string $name
     * @param array  $params
     * @param array  $query
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function redirectToRoute(string $name, array $params = [], array $query = []): RedirectResponse
    {
        return new RedirectResponse($this->path($name, $params, $query));
    }

    /**
     * @param string $name
     * @param array  $params
     * @param array  $query
     *
     * @return string
     * @throws Exception
     */
    public function path(string $name, array $params = [], array $query = []): string
    {
        $path = $this->routeGenerator->generate($name, $params);
        if ($query) {
            $query = http_build_query($query);
            if (strpos($path, '?') === false) {
                $path .= "?$query";
            } else {
                $path .= "&$query";
            }
        }

        return $path;
    }

    /**
     * @param string   $name
     * @param array    $params
     * @param array    $query
     * @param int|null $oid
     *
     * @return string
     * @throws Exception
     */
    public function url(string $name, array $params = [], array $query = [], ?int $oid = null): string
    {
        $url = $this->routeGenerator->generate($name, $params, 'absolute', $oid);
        if ($query) {
            $query = http_build_query($query);
            if (strpos($url, '?') === false) {
                $url .= "?$query";
            } else {
                $url .= "&$query";
            }
        }

        return $url;
    }

    /**
     * @param string      $type
     * @param object|null $modal
     * @param array       $options
     *
     * @return FormInterface
     */
    public function createForm(string $type, object $modal = null, array $options = []): FormInterface
    {
        return $this->formBuilder->create($type, $modal, $options);
    }

    /**
     * @param FormInterface $form
     *
     * @return bool
     */
    public function isSubmitted(FormInterface $form): bool
    {
        /** @phpstan-ignore-next-line */
        return $form->handleRequest() && $form->isSubmitted() && $form->isValid();
    }

    /**
     * @param string $message
     *
     * @throws Exception
     */
    public function throwNotFound(string $message = 'Not Found')
    {
        throw new NotFoundException($message);
    }

    /**
     * @param string $message
     *
     * @throws Exception
     */
    public function throwBadRequest(string $message = 'Bad Request')
    {
        throw new BadRequestException($message);
    }

    /**
     * @param string $message
     *
     * @throws Exception
     */
    public function throwUnauthorized(string $message = 'Unauthorized')
    {
        throw new UnauthorizedException($message);
    }

    /**
     * @param string $className
     * @param string $methodName
     * @param array  $params
     *
     * @return Forward
     */
    public function forward(string $className, string $methodName, array $params = []): Forward
    {
        return new Forward($className, $methodName, $params);
    }

    /**
     * @param Request $request
     * @param array   $initial
     *
     * @return Response
     * @throws Exception
     */
    public function renderFrontend(Request $request, array $initial = []): Response
    {
        return $this->render('dashboard/index.html.twig', [
            'initialState' => $this->getFrontendState($request, $initial)
        ]);
    }

    /**
     * @param Request $request
     * @param array   $initial
     *
     * @return array
     * @throws Exception
     */
    public function getFrontendState(Request $request, array $initial = []): array
    {
        $me                     = null;
        $organizations          = [];
        $notifications          = [];
        $serializer             = $this->container->get(Serializer::class);
        $orgsRepository         = $this->container->get(OrganizationsRepository::class);
        $orgAccessRepository    = $this->container->get(OrganizationAccessRepository::class);
        $authService            = $this->container->get(AuthService::class);
        $userRepository         = $this->container->get(UserRepository::class);
        $notificationRepository = $this->container->get(NotificationRepository::class);
        $redis                  = $this->container->get(Redis::class);
        $files                  = $this->container->get(Files::class);
        $mentions               = $this->container->get(Mentions::class);
        $uid                    = (int)$authService->getLoginId();
        $oid                    = $request->getOrgSubdomain();

        if ($uid) {
            $user = $userRepository->findByID($uid, true);
            $me   = $serializer->serializeUser($user);
            $orgs = $orgsRepository->findByUser($user->getId());
            foreach ($orgs as $org) {
                if ($org['org_id']) {
                    $owner           = (int)$orgAccessRepository->findInitialOwnerID($org['org_id']);
                    $organizations[] = [
                        'id'      => (int)$org['org_id'],
                        'name'    => $org['org_name'],
                        'domain'  => $request->getDomainForOrg($org['org_id']),
                        'isOwner' => ($owner && $uid === $owner)
                    ];
                }
            }

            $orgOwner            = $orgAccessRepository->findInitialOwner($oid);
            $me['dashboardUrl']  = $request->getDomainForOrg();
            $me['organizations'] = $organizations;
            $me['isOwner']       = (int)$orgOwner['usr_id'] === $uid;
            $me['isAdmin']       = $orgAccessRepository->isAdmin($uid, $oid);

            foreach($notificationRepository->findByTo($user) as $notification) {
                if ($notification->getMention()) {
                    $mentions->updateAll($notification->getMention()->getComment());
                }
                $notifications[] = $serializer->serializeNotification($notification);
            }

            $cacheKey = sprintf('last.organization.%d', $uid);
            $lastOid = $redis->get($cacheKey);
            if ($lastOid && (int)$lastOid) {
                $me['lastDashboard'] = $request->getDomainForOrg((int)$lastOid);
            }
        }

        return array_merge([
            'users' => [
                'me'          => $me,
                'account'     => null,
                'idProviders' => []
            ],
            'socket' => [
                'url'  => $this->config->socket['url'],
                'path' => $this->config->socket['path']
            ],
            'notifications' => $notifications,
            'webPushPubKey' => $files->read(Paths::combine($this->config->dirs['config'], 'certs/vapid.pub'))
        ], $initial);
    }
}
