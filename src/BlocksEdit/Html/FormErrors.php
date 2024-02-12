<?php
namespace BlocksEdit\Html;

/**
 * Class FormErrors
 */
class FormErrors
{
    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param string $field
     * @param string $message
     *
     * @return $this
     */
    public function add(string $field, string $message): FormErrors
    {
        $this->errors[$field] = $message;

        return $this;
    }

    /**
     * @return array
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @param string $field
     *
     * @return string
     */
    public function getError(string $field): string
    {
        if (isset($this->errors[$field])) {
            return $this->errors[$field];
        }

        return '';
    }

    /**
     * @return bool
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * @param string $field
     *
     * @return bool
     */
    public function hasError(string $field): bool
    {
        return !empty($this->errors[$field]);
    }
}
