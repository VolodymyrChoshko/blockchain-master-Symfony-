<?php
namespace BlocksEdit\Form;

use BlocksEdit\Html\FormErrors;
use Exception;

/**
 * Class FormBuilder
 */
class FormBuilder
{
    /**
     * @return FormBuilder
     */
    public static function create(): FormBuilder
    {
        /** @phpstan-ignore-next-line */
        return new static();
    }

    /**
     * @param string     $name
     * @param array      $item
     * @param array      $attribs
     * @param FormErrors $errors
     *
     * @return string
     */
    public function widget(string $name, array $item, array $attribs = [], $errors = null): string
    {
        if (isset($item['required'])) {
            $attribs['required'] = $item['required'];
        }

        try {
            $html = '';
            switch ($item['type']) {
                case 'text':
                    $html = $this->widgetText($name, $item, $attribs);
                    break;
                case 'number':
                    $html = $this->widgetNumber($name, $item, $attribs);
                    break;
                case 'hidden':
                    $html = $this->widgetHidden($name, $item, $attribs);
                    break;
                case 'file':
                    $html = $this->widgetFile($name, $item, $attribs);
                    break;
                case 'password':
                    $html = $this->widgetPassword($name, $item, $attribs);
                    break;
                case 'choice':
                    $html = $this->widgetChoice($name, $item, $attribs);
                    break;
                case 'checkbox':
                    $html = $this->widgetCheckbox($name, $item, $attribs);
                    break;
                default:
                    throw new Exception(
                        "Unknown form type ${item['type']}."
                    );
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }

        if (!empty($item['help'])) {
            $html .= sprintf('<div class="form-help">%s</div>', $item['help']);
        }

        if ($errors instanceof FormErrors && $errors->hasError($name)) {
            return sprintf(
                '<div class="form-widget form-widget-error"><div class="form-widget-error-msg">%s</div>%s</div>',
                $errors->getError($name),
                $html
            );
        }

        return sprintf('<div class="form-widget">%s</div>', $html);
    }

    /**
     * @param string  $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    function widgetText(string $name, array $item, array $attribs = []): string
    {
        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= $this->widgetInput('text', $name, $item, $attribs);

        return $html;
    }

    /**
     * @param string  $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetNumber(string $name, array $item, array $attribs = []): string
    {
        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= $this->widgetInput('number', $name, $item, $attribs);

        return $html;
    }

    /**
     * @param string  $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetHidden(string $name, array $item, array $attribs = []): string
    {
        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= $this->widgetInput('hidden', $name, $item, $attribs);

        return $html;
    }

    /**
     * @param string  $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetFile(string $name, array $item, array $attribs = []): string
    {
        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= $this->widgetInput('file', $name, $item, $attribs);

        return $html;
    }

    /**
     * @param string  $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetPassword(string $name, array $item, array $attribs = []): string
    {
        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= $this->widgetInput('password', $name, $item, $attribs);

        return $html;
    }

    /**
     * @param string $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetChoice(string $name, array $item, array $attribs = []): string
    {
        $id        = $this->buildID($name, $item);
        $attribs   = $this->addClassAttrib($name, $attribs);
        $attribs   = $this->buildAttribs($attribs);
        $itemValue = !empty($item['value']) ? $item['value'] : '';

        $html = '';
        if (isset($item['label'])) {
            $html .= $this->widgetLabel($name, $item);
        }
        $html .= "<select name=\"${name}\" id=\"${id}\" ${attribs}>";
        foreach($item['choices'] as $value => $label) {
            $value = htmlspecialchars($value);
            $label = htmlspecialchars($label);
            if ($value === $itemValue) {
                $html .= "<option value=\"${value}\" selected='selected'>${label}</option>";
            } else {
                $html .= "<option value=\"${value}\">${label}</option>";
            }
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @param string $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetCheckbox(string $name, array $item, array $attribs = []): string
    {
        if (!empty($item['value'])) {
            $attribs['checked'] = 'checked';
        }
        $label   = htmlspecialchars($item['label']);
        $attribs = $this->addClassAttrib($name, $attribs);
        $attribs = $this->buildAttribs($attribs);
        $id      = $this->buildID($name, $item);

        return <<<HTML
    <label for="${id}">
        ${label}
        <input type="checkbox" name="${name}" id="${id}" ${attribs} />
        <input type="hidden" name="__${name}" value="0" />
    </label>
HTML;
    }

    /**
     * @param string $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetLabel(string $name, array $item, array $attribs = []): string
    {
        $label   = htmlspecialchars($item['label']);
        $attribs = $this->buildAttribs($attribs);
        $id      = $this->buildID($name, $item);

        return <<<HTML
<label for="${id}" ${attribs}>
    ${label}
</label>
HTML;
    }

    /**
     * @param string $type
     * @param string $name
     * @param array  $item
     * @param array  $attribs
     *
     * @return string
     */
    public function widgetInput(string $type, string $name, array $item, array $attribs = []): string
    {
        $value = !empty($item['value']) ? htmlspecialchars($item['value']) : '';
        if (!empty($item['required'])) {
            $attribs['required'] = 'required';
        }
        if (!empty($item['readOnly'])) {
            $attribs['readonly'] = 'readonly';
        }
        if ($type === 'file' && !empty($value)) {
            $value = '';
            $attribs['data-is-set'] = 1;
        }

        $id      = $this->buildID($name, $item);
        $attribs = $this->addClassAttrib($name, $attribs);
        $attribs = $this->buildAttribs($attribs);

        return <<<HTML
<input type="${type}" name="${name}" id="${id}" value="${value}" ${attribs} />
HTML;
    }

    /**
     * @param array $attribs
     *
     * @return string
     */
    protected function buildAttribs(array $attribs): string
    {
        $built = [];
        foreach($attribs as $name => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $built[] = $name;
                } else {
                    $built[] = "${name}=\"false\"";
                }
            } else {
                $value   = htmlspecialchars($value);
                $built[] = "${name}=\"${value}\"";
            }
        }

        return join(' ', $built);
    }

    /**
     * @param string $name
     * @param array  $item
     *
     * @return string
     */
    protected function buildID(string $name, array $item): string
    {
        $name = str_replace('_', '-', $name);
        $id   = "form-item-${name}";
        if (isset($item['id_suffix'])) {
            $id .= '-' . $item['id_suffix'];
        }

        return $id;
    }

    /**
     * @param string $name
     * @param array  $attribs
     *
     * @return array
     */
    protected function addClassAttrib(string $name, array $attribs): array
    {
        if (isset($attribs['class'])) {
            $attribs['class'] .= ' ' . "form-control form-control-item-${name}";
        } else {
            $attribs['class'] = "form-control form-control-item-${name}";
        }

        return $attribs;
    }
}
