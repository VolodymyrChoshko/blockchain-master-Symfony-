<?php
namespace Controller\Build;

use BlocksEdit\Controller\Controller;
use BlocksEdit\Html\DomParser;
use BlocksEdit\System\Serializer;
use Entity\SectionLibrary;
use Entity\Template;
use Entity\User;
use Exception;
use Repository\AccessRepository;
use Repository\OrganizationAccessRepository;
use Repository\SourcesRepository;
use Repository\TemplateSourcesRepository;
use Repository\TemplatesRepository;
use simplehtmldom_1_5\simple_html_dom;

/**
 * Class BuildController
 */
abstract class BuildController extends Controller
{
    /**
     * @param string $html
     *
     * @return array
     */
    protected function getHtmlDomAndGroups(string $html): array
    {
        $dom = DomParser::fromString($html);
        foreach($dom->find('*[data-style]') as $element) {
            if ($element->tag !== 'a') {
                $element->setAttribute('data-group', $element->getAttribute('data-style'));
            }
        }

        $groups     = [];
        $linkStyles = [];
        $lastGroup  = '';

        foreach($dom->find('a') as $anchor) {
            if ($anchor->getAttribute('data-style')) {
                if ($style = $anchor->getAttribute('style')) {
                    if (!in_array($style, $linkStyles)) {
                        $linkStyles[] = $style;
                    }
                }
            }
        }

        /*foreach($dom->find('.block-section') as $element) {
            foreach($element->find('.block-component') as $e) {
                if (strpos($e->getAttribute('class'), 'be-code-edit') === false && !$e->getAttribute('data-be-keep')) {
                    $style = $e->getAttribute('style');
                    if (!$style) {
                        $style = '';
                    }
                    $style .= ';display: none;';
                    $e->setAttribute('style', $style);
                    // $e->outertext = '';
                }
            }
        }*/

        foreach($dom->find('*[data-group]') as $element) {
            $group = $element->getAttribute('data-group');

            if ($group === $lastGroup && !$element->getAttribute('data-be-keep')) {
                if (!isset($groups[$group])) {
                    $groups[$group] = [
                        'activeIndex' => 0,
                        'items'       => []
                    ];
                    $groups[$group]['items'][] = $element->outertext();
                } else {
                    /* $section = $this->findParentSection($element);
                    if ($section && $section->getAttribute('data-group')) {
                        $parentGroup = $section->getAttribute('data-group');
                    } */

                    $groups[$group]['items'][] = $element->outertext();
                    $element->outertext        = '';
                }
                continue;
            }

            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'activeIndex' => 0,
                    'items'       => []
                ];
                $groups[$group]['items'][] = $element->outertext();
            } else if (!$element->getAttribute('data-be-keep')) {
                $groups[$group]['items'][] = $element->outertext();
                $element->outertext = '';
            }

            $lastGroup = $group;
        }

        return [$dom, $groups, $linkStyles];
    }

    /**
     * @param User|null $user
     * @param string    $mode
     * @param int       $id
     * @param int       $tid
     * @param int       $oid
     *
     * @return array
     * @throws Exception
     */
    protected function getInitialState(
        ?User $user,
        string $mode,
        int $id,
        int $tid = 0,
        int $oid = 0)
    : array
    {
        $billingPlan = null;
        if ($user) {
            $billingPlan = $this->getFrontendBillingPlan($user, $oid);
        }

        return [
            'builder' => [
                'id'      => $id,
                'tid'     => $tid,
                'mode'    => $mode,
                'editing' => $mode === 'template' || $mode === 'template_preview',
                'isEmpty' => false
            ],
            'billingPlan' => $billingPlan
        ];
    }

    /**
     * @param int $id
     * @param int $oid
     *
     * @return array
     * @throws Exception
     */
    protected function getSources(int $id, int $oid): array
    {
        $billingPlan = $this->getBillingPlan();
        if (!$billingPlan->isCustom()) {
            if ($billingPlan->isPaused() || ($billingPlan->isTrialComplete() && !$this->getBillingCreditCard())) {
                return [];
            }
        }

        $sources             = [];
        $templateSourcesRepo = $this->container->get(TemplateSourcesRepository::class);
        foreach($this->container->get(SourcesRepository::class)->findByOrg($oid) as $source) {
            if ($templateSourcesRepo->isEnabled($id, $source->getId())) {
                $sources[] = $source->toArray();
            }
        }

        return $sources;
    }

    /**
     * @param array  $sections
     * @param string $dateUpdated
     * @param int    $templateVersion
     *
     * @return array
     * @throws Exception
     */
    protected function filterSections(array $sections, string $dateUpdated, int $templateVersion): array
    {
        $filtered     = [];
        $foundDesktop = [];
        foreach($sections as $section) {
            $screenshotDesktop = '';
            $screenshotMobile  = '';
            if (!$section['sec_thumb']) {
                $screenshotDesktop = $this->paths->urlSectionScreenshot($section['sec_id'], false, $templateVersion);
                $screenshotMobile  = $this->paths->urlSectionScreenshot($section['sec_id'], true, $templateVersion);
                if (in_array($screenshotDesktop, $foundDesktop)) {
                    continue;
                }
                $foundDesktop[]    = $screenshotDesktop;
                $screenshotDesktop .= '?v=' . $dateUpdated;
                $screenshotMobile  .= '?v=' . $dateUpdated;
            }

            if (in_array(substr($section['sec_html'], 0, 3), ['<tr', '<td'])) {
                $html = '<table><tbody>' . $section['sec_html'] . '</tbody></table>';
            } else {
                $html = $section['sec_html'];
            }

            $dom = DomParser::fromString($html);
            foreach($dom->find('.block-component') as $element) {
                if (strpos($element->getAttribute('class'), 'be-code-edit') === false && !$element->getAttribute('data-be-keep')) {
                    $element->outertext = '';
                }
            }
            $html = (string)$dom;

            $filtered[] = [
                'type'              => 'section',
                'title'             => $section['sec_title'],
                'id'                => (int)$section['sec_id'],
                'nr'                => $section['sec_nr'],
                'tmp_id'            => (int)$section['sec_tmp_id'],
                'html'              => $html,
                'style'             => $section['sec_style'],
                'mobile'            => (bool)$section['sec_mobile'],
                'tmp_version'       => (int)$section['sec_tmp_version'],
                'thumb'             => $section['sec_thumb'],
                'screenshotDesktop' => $screenshotDesktop,
                'screenshotMobile'  => $screenshotMobile
            ];
        }

        return $filtered;
    }

    /**
     * @param array  $components
     * @param string $dateUpdated
     * @param int    $templateVersion
     *
     * @return array
     * @throws Exception
     */
    protected function filterComponents(array $components, string $dateUpdated, int $templateVersion): array
    {
        $filtered     = [];
        $foundDesktop = [];
        foreach($components as $component) {
            $screenshotDesktop = '';
            $screenshotMobile  = '';
            if (!$component['com_thumb']) {
                $screenshotDesktop = $this->paths->urlComponentScreenshot($component['com_id'], false, $templateVersion);
                $screenshotMobile  = $this->paths->urlComponentScreenshot($component['com_id'], true, $templateVersion);
                if (in_array($screenshotDesktop, $foundDesktop)) {
                    continue;
                }
                $foundDesktop[] = $screenshotDesktop;
                $screenshotDesktop .= '?v=' . $dateUpdated;
                $screenshotMobile .= '?v=' . $dateUpdated;
            }

            if (in_array(substr($component['com_html'], 0, 3), ['<tr', '<td'])) {
                $html = '<table><tbody>' . $component['com_html'] . '</tbody></table>';
            } else {
                $html = $component['com_html'];
            }

            $filtered[] = [
                'type'              => 'component',
                'title'             => $component['com_title'],
                'id'                => (int)$component['com_id'],
                'nr'                => $component['com_nr'],
                'tmp_id'            => (int)$component['com_tmp_id'],
                'html'              => $html,
                'style'             => $component['com_style'],
                'mobile'            => (bool)$component['com_mobile'],
                'tmp_version'       => (int)$component['com_tmp_version'],
                'thumb'             => $component['com_thumb'],
                'screenshotDesktop' => $screenshotDesktop,
                'screenshotMobile'  => $screenshotMobile
            ];
        }

        return $filtered;
    }

    /**
     * @param SectionLibrary[] $libraries
     * @param simple_html_dom  $origHtml
     *
     * @return array
     */
    protected function filterLibraries(array $libraries, simple_html_dom $origHtml): array
    {
        $filtered = [];
        foreach($libraries as $library) {
            $screenshotDesktop = '';
            $screenshotMobile  = '';

            $thumb = $library->getThumbnail();
            if (strpos($thumb, 'http') === 0) {
                $screenshotDesktop = $thumb;
                $screenshotMobile  = $thumb;
            } else if ($library->getThumbnail()) {
                $screenshotDesktop = sprintf(
                    '/screenshots/%s',
                    $library->getThumbnail()
                );
                $screenshotMobile  = sprintf(
                    '/screenshots/%s',
                    $library->getThumbnail()
                );
            }

            $filtered[] = [
                'type'              => 'section',
                'id'                => $library->getId(),
                'nr'                => 0,
                'name'              => $library->getName(),
                'tmp_id'            => $library->getTmpId(),
                'tmp_version'       => $library->getTmpVersion(),
                'html'              => $library->getHtml(),
                'style'             => $library->getGroup(),
                'mobile'            => $library->isMobile(),
                'isLibrary'         => true,
                'pinGroup'          => $library->getPinGroup() ? $library->getPinGroup()->getId() : null,
                'screenshotDesktop' => $screenshotDesktop,
                'screenshotMobile'  => $screenshotMobile,
                'isUpgradable'      => $this->isLibraryUpgradable($library, $origHtml),
            ];
        }

        return $filtered;
    }

    /**
     * @param array           $layouts
     * @param simple_html_dom $origHtml
     *
     * @return array
     * @throws Exception
     */
    protected function filterLayouts(array $layouts, simple_html_dom $origHtml): array
    {
        $serializer          = $this->container->get(Serializer::class);
        $templatesRepository = $this->container->get(TemplatesRepository::class);

        $filtered = [];
        foreach($layouts as $template) {
            $html   = $templatesRepository->getHtml($template['tmp_id'])->getDom();
            $layout = $serializer->serializeLayout($template);
            $this->logger->debug($layout['id']);
            $layout['isUpgradable'] = $this->isLayoutUpgradable($html, $origHtml);
            $filtered[] = $layout;
        }

        return $filtered;
    }

    /**
     * @param simple_html_dom $html
     * @param simple_html_dom $origHtml
     *
     * @return bool
     */
    protected function isLayoutUpgradable(simple_html_dom $html, simple_html_dom $origHtml): bool
    {
        foreach($html->find('.block-edit') as $item) {
            $blockName = $item->getAttribute('data-block');
            if (!$blockName) {
                $attr = '';
                foreach($item->getAllAttributes() as $key => $value) {
                    $attr .= "$key=\"$value\" ";
                }
                $this->logger->debug('Missing data-block on <' . $item->tag . ' ' . trim($attr) . '>');
                return false;
            }
            /*$templateBlock = $origHtml->find(".block-edit[data-block='$blockName']", 0);
            if (!$templateBlock) {
                $this->logger->debug("Missing template block .block-edit[data-block='$blockName']");
                return false;
            }*/
        }

        return true;
    }

    /**
     * @param SectionLibrary  $library
     * @param simple_html_dom $origHtml
     *
     * @return bool
     */
    protected function isLibraryUpgradable(SectionLibrary $library, simple_html_dom $origHtml): bool
    {
        $dom           = DomParser::fromString($library->getHtml());
        $groupName     = $dom->firstChild()->getAttribute('data-group');
        $templateBlock = $origHtml->find(".block-section[data-group='$groupName']", 0);
        if (!$templateBlock) {
            return false;
        }

        foreach($dom->find('.block-edit') as $item) {
            $blockName = $item->getAttribute('data-block');
            if (!$blockName) {
                return false;
            }
            /*$templateBlock = $origHtml->find(".block-edit[data-block='$blockName']", 0);
            if (!$templateBlock) {
                return false;
            }*/
        }

        return true;
    }

    /**
     * @param Template $template
     *
     * @return User[]
     * @throws Exception
     */
    protected function getTemplatePeople(Template $template): array
    {
        $people = [];
        $accessRepository = $this->container->get(AccessRepository::class);
        $orgAccessRepository = $this->container->get(OrganizationAccessRepository::class);
        foreach($orgAccessRepository->findOwners($template->getOrganization()) as $roleAccess) {
            $user = $roleAccess->getUser();
            if ($user) {
                $user->setIsResponded(true);
                $user->setIsOwner(true);
                if (!isset($people[$user->getId()])) {
                    $people[$user->getId()] = $user;
                }
            }
        }
        foreach($orgAccessRepository->findAdmins($template->getOrganization()) as $roleAccess) {
            $user = $roleAccess->getUser();
            if ($user) {
                $user->setIsResponded(true);
                $user->setIsAdmin(true);
                if (!isset($people[$user->getId()])) {
                    $people[$user->getId()] = $user;
                }
            }
        }
        foreach($accessRepository->findByTemplate($template) as $access) {
            $author = $access->getUser();
            if (isset($people[$author->getId()])) {
                continue;
            }
            $people[$author->getId()] = $author;
            if ($access->isResponded()) {
                $author->setIsResponded(true);
            }
        }

        return $people;
    }
}
