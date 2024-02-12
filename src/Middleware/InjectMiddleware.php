<?php
namespace Middleware;

use BlocksEdit\Http\Middleware;
use BlocksEdit\Http\Annotations\InjectTemplate;
use BlocksEdit\Http\Annotations\InjectEmail;
use BlocksEdit\Http\Exception\NotFoundException;
use BlocksEdit\Http\RedirectResponse;
use BlocksEdit\Http\Request;
use Doctrine\Common\Annotations\AnnotationReader;
use Entity\Email;
use Entity\Template;
use Exception;
use ReflectionClass;
use Repository\EmailRepository;
use Repository\TemplatesRepository;

/**
 * Class InjectMiddleware
 */
class InjectMiddleware extends Middleware
{
    /**
     * @return int
     */
    public function getPriority(): int
    {
        return 2;
    }

    /**
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function request(Request $request): ?RedirectResponse
    {
        $controller = $request->route->params->get('_controller');
        $method     = $request->route->params->get('_method');

        $reader           = new AnnotationReader();
        $reflectionClass  = new ReflectionClass($controller);
        $reflectionMethod = $reflectionClass->getMethod($method);

        $injectTemplate = $reader->getMethodAnnotation($reflectionMethod, InjectTemplate::class);
        $injectEmail    = $reader->getMethodAnnotation($reflectionMethod, InjectEmail::class);
        if ($injectTemplate) {
            $type = 'array';
            foreach($reflectionMethod->getParameters() as $param) {
                if ($param->getName() === 'template') {
                    $type = (string)$param->getType();
                    break;
                }
            }

            $id = $request->route->params->get($injectTemplate->value);
            if (!$id) {
                throw new NotFoundException();
            }
            $template = $this->container->get(TemplatesRepository::class)
                ->findByID($id, $type === Template::class);
            if (!$template) {
                throw new NotFoundException();
            }
            $request->route->params->set($injectTemplate->param, $template);
        } else if ($injectEmail) {
            $type = 'array';
            foreach($reflectionMethod->getParameters() as $param) {
                if ($param->getName() === 'email') {
                    $type = (string)$param->getType();
                    break;
                }
            }

            $id = $request->route->params->getInt($injectEmail->value);
            if (!$id) {
                throw new NotFoundException();
            }
            $email = $this->container->get(EmailRepository::class)->findByID($id, $type === Email::class);
            if (!$email) {
                throw new NotFoundException();
            }
            $request->route->params->set($injectEmail->param, $email);

            if ($injectEmail->includeTemplate) {
                $template = $this->container->get(TemplatesRepository::class)->findByID($email['ema_tmp_id']);
                if (!$template) {
                    throw new NotFoundException();
                }
                $request->route->params->set($injectEmail->templateParam, $template);
            }
        }

        return null;
    }
}
