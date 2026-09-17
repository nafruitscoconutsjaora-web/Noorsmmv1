<?php

declare(strict_types=1);

namespace App\Validation;

use App\Core\Database;
use App\Exceptions\ValidationException;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];

    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }

    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }

    public function validate(): array
    {
        foreach ($this->rules as $field => $fieldRules) {
            $rulesArray = is_array($fieldRules) ? $fieldRules : explode('|', $fieldRules);
            $val = $this->data[$field] ?? null;

            foreach ($rulesArray as $rule) {
                $params = [];
                if (str_contains($rule, ':')) {
                    [$ruleName, $paramStr] = explode(':', $rule, 2);
                    $params = explode(',', $paramStr);
                } else {
                    $ruleName = $rule;
                }

                $this->applyRule($field, $val, $ruleName, $params);
            }
        }

        if (!empty($this->errors)) {
            throw new ValidationException($this->errors);
        }

        return $this->data;
    }

    public function fails(): bool
    {
        try {
            $this->validate();
            return false;
        } catch (ValidationException $e) {
            return true;
        }
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function applyRule(string $field, mixed $val, string $rule, array $params): void
    {
        $label = str_replace('_', ' ', ucfirst($field));

        switch ($rule) {
            case 'required':
                if ($val === null || $val === '' || (is_array($val) && empty($val))) {
                    $this->addError($field, "{$label} is required.");
                }
                break;

            case 'email':
                if (!empty($val) && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "{$label} must be a valid email address.");
                }
                break;

            case 'url':
                if (!empty($val) && !filter_var($val, FILTER_VALIDATE_URL)) {
                    $this->addError($field, "{$label} must be a valid URL.");
                }
                break;

            case 'numeric':
                if (!empty($val) && !is_numeric($val)) {
                    $this->addError($field, "{$label} must be a valid number.");
                }
                break;

            case 'integer':
                if (!empty($val) && filter_var($val, FILTER_VALIDATE_INT) === false) {
                    $this->addError($field, "{$label} must be an integer.");
                }
                break;

            case 'min':
                $min = (int)($params[0] ?? 0);
                if (is_numeric($val) && (float)$val < $min) {
                    $this->addError($field, "{$label} must be at least {$min}.");
                } elseif (is_string($val) && mb_strlen($val) < $min) {
                    $this->addError($field, "{$label} must be at least {$min} characters.");
                }
                break;

            case 'max':
                $max = (int)($params[0] ?? 0);
                if (is_numeric($val) && (float)$val > $max) {
                    $this->addError($field, "{$label} may not be greater than {$max}.");
                } elseif (is_string($val) && mb_strlen($val) > $max) {
                    $this->addError($field, "{$label} may not be greater than {$max} characters.");
                }
                break;

            case 'same':
                $otherField = $params[0] ?? '';
                $otherVal = $this->data[$otherField] ?? null;
                if ($val !== $otherVal) {
                    $otherLabel = str_replace('_', ' ', ucfirst($otherField));
                    $this->addError($field, "{$label} must match {$otherLabel}.");
                }
                break;

            case 'in':
                if (!empty($val) && !in_array((string)$val, $params, true)) {
                    $this->addError($field, "{$label} is invalid.");
                }
                break;

            case 'unique':
                $table = $params[0] ?? '';
                $column = $params[1] ?? $field;
                $exceptId = $params[2] ?? null;

                if (!empty($val) && !empty($table)) {
                    $db = Database::getInstance();
                    $sql = "SELECT COUNT(*) as cnt FROM `{$table}` WHERE `{$column}` = :val";
                    $binds = [':val' => $val];
                    if ($exceptId !== null && $exceptId !== '') {
                        $sql .= " AND `id` != :exceptId";
                        $binds[':exceptId'] = $exceptId;
                    }
                    $row = $db->fetchOne($sql, $binds);
                    if ($row && (int)$row['cnt'] > 0) {
                        $this->addError($field, "{$label} is already in use.");
                    }
                }
                break;
        }
    }

    private function addError(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }
}
