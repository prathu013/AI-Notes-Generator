<?php
// ============================================================
// includes/Validator.php — Input validation helper
// ============================================================

class Validator {
    private array $errors = [];
    private array $data   = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    // ── Static factory ───────────────────────────────────────
    public static function make(array $data, array $rules): self {
        $v = new self($data);
        foreach ($rules as $field => $ruleSet) {
            $ruleList = is_array($ruleSet) ? $ruleSet : explode('|', $ruleSet);
            foreach ($ruleList as $rule) {
                $v->applyRule($field, $rule);
            }
        }
        return $v;
    }

    // ── Apply a single rule ──────────────────────────────────
    private function applyRule(string $field, string $rule): void {
        $value = $this->data[$field] ?? null;

        if (str_starts_with($rule, 'min:')) {
            $min = (int) substr($rule, 4);
            if ($value !== null && mb_strlen((string)$value) < $min) {
                $this->errors[$field][] = ucfirst($field) . " must be at least {$min} characters.";
            }
            return;
        }

        if (str_starts_with($rule, 'max:')) {
            $max = (int) substr($rule, 4);
            if ($value !== null && mb_strlen((string)$value) > $max) {
                $this->errors[$field][] = ucfirst($field) . " must not exceed {$max} characters.";
            }
            return;
        }

        match ($rule) {
            'required' => (!isset($this->data[$field]) || trim((string)($value ?? '')) === '')
                ? $this->errors[$field][] = ucfirst($field) . ' is required.'
                : null,

            'email' => ($value && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? $this->errors[$field][] = 'Please enter a valid email address.'
                : null,

            'string' => ($value !== null && !is_string($value))
                ? $this->errors[$field][] = ucfirst($field) . ' must be a string.'
                : null,

            'integer' => ($value !== null && !is_numeric($value))
                ? $this->errors[$field][] = ucfirst($field) . ' must be an integer.'
                : null,

            default => null,
        };
    }

    // ── Results ──────────────────────────────────────────────
    public function passes(): bool  { return empty($this->errors); }
    public function fails(): bool   { return !$this->passes(); }
    public function errors(): array { return $this->errors; }

    // ── Get sanitized field ──────────────────────────────────
    public function get(string $field, mixed $default = null): mixed {
        return $this->data[$field] ?? $default;
    }

    // ── Parse JSON body ──────────────────────────────────────
    public static function json(): array {
        $raw = file_get_contents('php://input');
        if (empty($raw)) return [];
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
