<?php

namespace Nexa\Http\Middleware;

use Nexa\Http\Request;
use Nexa\Http\Response;

class ValidationMiddleware
{
    protected $rules = [];
    protected $messages = [];
    
    /**
     * Set validation rules.
     *
     * @param array $rules
     * @return $this
     */
    public function rules(array $rules)
    {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Set custom error messages.
     *
     * @param array $messages
     * @return $this
     */
    public function messages(array $messages)
    {
        $this->messages = $messages;
        return $this;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next)
    {
        $errors = $this->validate($request->all());
        
        if (!empty($errors)) {
            return $this->validationError($errors);
        }
        
        return $next($request);
    }
    
    /**
     * Validate request data.
     *
     * @param array $data
     * @return array
     */
    protected function validate(array $data)
    {
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $fieldRules = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($fieldRules as $rule) {
                $error = $this->validateField($field, $data[$field] ?? null, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate a single field.
     *
     * @param string $field
     * @param mixed $value
     * @param string $rule
     * @return string|null
     */
    protected function validateField($field, $value, $rule)
    {
        $ruleParts = explode(':', $rule);
        $ruleName = $ruleParts[0];
        $ruleValue = $ruleParts[1] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value)) {
                    return $this->getMessage($field, 'required', 'The :field field is required.');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return $this->getMessage($field, 'email', 'The :field must be a valid email address.');
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$ruleValue) {
                    return $this->getMessage($field, 'min', "The :field must be at least {$ruleValue} characters.");
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$ruleValue) {
                    return $this->getMessage($field, 'max', "The :field may not be greater than {$ruleValue} characters.");
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    return $this->getMessage($field, 'numeric', 'The :field must be a number.');
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return $this->getMessage($field, 'url', 'The :field must be a valid URL.');
                }
                break;
                
            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if (!empty($value) && !in_array($value, $allowedValues)) {
                    $allowed = implode(', ', $allowedValues);
                    return $this->getMessage($field, 'in', "The :field must be one of: {$allowed}.");
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (!empty($value) && $value !== ($_POST[$confirmField] ?? null)) {
                    return $this->getMessage($field, 'confirmed', 'The :field confirmation does not match.');
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Get error message for field and rule.
     *
     * @param string $field
     * @param string $rule
     * @param string $default
     * @return string
     */
    protected function getMessage($field, $rule, $default)
    {
        $key = "{$field}.{$rule}";
        $message = $this->messages[$key] ?? $this->messages[$rule] ?? $default;
        
        return str_replace(':field', $field, $message);
    }
    
    /**
     * Return validation error response.
     *
     * @param array $errors
     * @return Response
     */
    protected function validationError(array $errors)
    {
        return new Response([
            'error' => 'Validation failed',
            'errors' => $errors
        ], 422, ['Content-Type' => 'application/json']);
    }
    
    /**
     * Create a new validation middleware instance with rules.
     *
     * @param array $rules
     * @param array $messages
     * @return static
     */
    public static function make(array $rules, array $messages = [])
    {
        return (new static())->rules($rules)->messages($messages);
    }
}