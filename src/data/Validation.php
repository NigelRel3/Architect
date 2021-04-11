<?php
namespace Architect\data;

trait Validation	{
	public static $intValidateOptions = ['options' =>
			['min_range' => 1,	'max_range' => PHP_INT_MAX]
	];
	
	protected function validateID ( array &$errors, string $dataLabel = 'id' )	{
		if ( ($this->active === false && is_null($this->data[$dataLabel])) ||
				filter_var($this->data[$dataLabel], FILTER_VALIDATE_INT,
						Validation::$intValidateOptions) !== false) {
		}
		else {
			$errors[] = "Invalid {$dataLabel} '{$this->data[$dataLabel]}'";
		}
		
	}
	
	protected function validateNotNull ( array &$errors, string $dataLabel )	{
		if (is_null($this->data[$dataLabel])) {
			$errors[] = "{$dataLabel} cannot be null";
		}
	}
	
	protected function validateLength ( array &$errors, string $dataLabel,
			int $minLength, int $maxLength
			)	{
		$length = strlen($this->data[$dataLabel]);
		if ( ! is_null($this->data[$dataLabel]) && $length < $minLength ) {
			$errors[] = "Min length of {$dataLabel} is {$minLength}, actual " .	$length;
		}
		
		if ($length > $maxLength) {
			$errors[] = "Max length of {$dataLabel} is {$maxLength}, actual " . $length;
		}
	}
	
	// TODO Check dates are processed
	
}