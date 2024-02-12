<?php

use BlocksEdit\Form\FormBuilder;
use BlocksEdit\Html\FormErrors;
use BlocksEdit\View\View;

/** @var View $__view */
$__view = null;
$__formBuilder = new FormBuilder();

/**
 * @param View $view
 */
function setView(View $view)
{
    global $__view;
    $__view = $view;
}

/**
 * @param mixed $text
 *
 * @return string
 */
function esc($text): string
{
    global $__view;

    return $__view->escape($text);
}

/**
 * @param string $layout
 */
function layout(string $layout)
{
    global $__view;

    $__view->setLayout($layout);
}

/**
 * @param string $template
 * @param array  $vars
 *
 * @return mixed
 */
function includeTemplate(string $template, $vars = [])
{
    global $__view;

    return $__view->includeTemplate($template, $vars);
}

/**
 * @param string $path
 *
 * @return string
 */
function asset(string $path): string
{
    global $__view;

    if (stripos($path, 'http') === 0) {
        return $path;
    }

    $path = trim($path, '/');
    if (strpos($path, '?') === false) {
        $path .= '?v=' . $__view->assetsVersion;
    } else {
        $path .= '&v=' . $__view->assetsVersion;
    }

    return $__view->assetsUri . "/${path}";
}

/**
 * @param mixed $user
 * @param int   $size
 * @param array $attribs
 *
 * @return string
 */
function avatar($user, $size = 60, array $attribs = []): string
{
    global $__view;

    if (!is_array($user)) {
        return '';
    }

    if (!$size) {
        $size = 60;
    }

    if (empty($user['usr_avatar'])) {
        $initials = '';
        $words = explode(' ', $user['usr_name']);
        foreach ($words as $word) {
            if (!isset($word[0])) {
                continue;
            }
            $initials .= strtoupper($word[0]);
        }

        return sprintf('<div class="avatar" title="%s">%s</div>', $user['usr_name'], $initials);
    }

    $size = "${size}x${size}";
    list($name, $ext) = explode('.',  $user['usr_avatar']);

    return sprintf(
        '<img src="%s/%s-%s.%s" title="%s" class="avatar" alt="Avatar" %s />',
        $__view->avatarsUri,
        $name,
        $size,
        $ext,
        $user['usr_name'],
        _buildAttribs($attribs)
    );
}

/**
 * @param string $name
 * @param array  $params
 * @param string $type
 * @param null   $oid
 *
 * @return string
 */
function path(string $name, array $params = [], $type = 'relative', $oid = null): string
{
    try {
        $path = View::getRouteGenerator()->generate($name, $params, $type, $oid);
    } catch (Exception $e) {
        trigger_error($e->getMessage());
        $path = '';
    }

    return $path;
}

/**
 * @param string $form
 * @param int    $expiration
 *
 * @return string
 */
function generateNonce(string $form, $expiration = 3600): string
{
    return View::getNonceGenerator()->generate($form, $expiration);
}

/**
 * @param string $form
 * @param int    $expiration
 *
 * @return string
 */
function nonce(string $form, $expiration = 3600): string
{
    $nonce = generateNonce($form, $expiration);

    return sprintf('<input type="hidden" name="token" value="%s" />', esc($nonce));
}

/**
 * @param int    $num
 * @param string $singular
 * @param string $plural
 *
 * @return string
 */
function pluralize(int $num, string $singular, string $plural): string
{
    return ($num == 1) ? $singular : $plural;
}

/**
 * @param string $grant
 *
 * @return bool
 */
function isGranted(string $grant): bool
{
    global $__view;

    $grants = $__view->request->session->get('security.grants', []);

    return in_array($grant, $grants);
}

/**
 * @param string     $name
 * @param array      $item
 * @param array      $attribs
 * @param FormErrors $errors
 *
 * @return string
 */
function formWidget(string $name, array $item, array $attribs = [], $errors = null): string
{
    global $__formBuilder;
    if (!$__formBuilder) {
        $__formBuilder = new FormBuilder();
    }

    return $__formBuilder->widget($name, $item, $attribs, $errors);
}

/**
 * @param array $attribs
 *
 * @return string
 */
function _buildAttribs(array $attribs): string
{
    $built = [];
    foreach($attribs as $name => $value) {
        $value = htmlspecialchars($value);
        $built[] = "${name}=\"${value}\"";
    }

    return join(' ', $built);
}

