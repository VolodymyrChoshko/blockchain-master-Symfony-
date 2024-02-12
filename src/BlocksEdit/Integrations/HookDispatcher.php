<?php
namespace BlocksEdit\Integrations;

use Exception;
use Psr\Log\LoggerAwareTrait;
use Repository\SourcesRepository;
use Repository\TemplateSourcesRepository;

/**
 * Class HookDispatcher
 */
class HookDispatcher
{
    use LoggerAwareTrait;

    /**
     * @var SourcesRepository
     */
    protected $sourcesRepository;

    /**
     * @var TemplateSourcesRepository
     */
    protected $templateSourcesRepository;

    /**
     * @var int
     */
    protected $uid = 0;

    /**
     * @var int
     */
    protected $oid = 0;

    /**
     * Constructor
     *
     * @param SourcesRepository         $sourcesRepository
     * @param TemplateSourcesRepository $templateSourcesRepository
     * @param int                       $uid
     * @param int                       $oid
     */
    public function __construct(
        SourcesRepository $sourcesRepository,
        TemplateSourcesRepository $templateSourcesRepository,
        int $uid,
        int $oid
    ) {
        $this->sourcesRepository         = $sourcesRepository;
        $this->templateSourcesRepository = $templateSourcesRepository;
        $this->uid                       = $uid;
        $this->oid                       = $oid;
    }

    /**
     * @param Hook $hook
     * @param int  $sid
     *
     * @return Hook
     * @throws Exception
     */
    public function dispatch(Hook $hook, int $sid = 0): Hook
    {
        $name  = $hook->getName();
        $email = $hook->getEmail();
        foreach($this->sourcesRepository->findByOrg($this->oid) as $source) {
            if ($sid && $source->getId() !== $sid) {
                continue;
            }
            if ($email && !$this->templateSourcesRepository->isEnabled($email['ema_tmp_id'], $source->getId())) {
                continue;
            }

            $integration      = $this->sourcesRepository->integrationFactory($source, $this->uid, $this->oid);
            $frontendSettings = $integration->getFrontendSettings();
            if (!empty($frontendSettings['hooks'][$name])) {
                try {
                    $callback = $frontendSettings['hooks'][$name];
                    call_user_func($callback, $hook);
                    $hook->addDispatchedSource($source->setIntegration($integration));
                } catch (Exception $e) {}
            }
        }

        return $hook;
    }
}
