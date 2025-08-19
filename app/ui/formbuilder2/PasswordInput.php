<?php

namespace App\UI\FormBuilder2;

/**
 * Form password input
 * 
 * @author Lukas Velek
 */
class PasswordInput extends AInput {
    public const COMPLEXITY_TEXT = 1;
    public const COMPLEXITY_TEXT_NUMBERS = 2;
    public const COMPLEXITY_TEXT_NUMBERS_SPECIAL_CHARS = 3;

    /**
     * Class constructor
     * 
     * @param string $name Element name
     */
    public function __construct(string $name) {
        parent::__construct('password', $name);
    }

    /**
     * Sets password complexity requirements
     * 
     * @param int $minLength Minimum length
     * @param int $maxLength Maximum length
     * @param int $complexity Complexity
     */
    public function setPasswordComplexityRequirements(int $minLength, int $maxLength, int $complexity): static {
        $this->additionalCode[] = '<span id="' . $this->name . '-form-password-complexity-check-message" style="color: red"></span>';

        $complexityText = '';
        if($complexity == self::COMPLEXITY_TEXT) {
            $complexityText = 'a-zA-Z';
        } else if($complexity == self::COMPLEXITY_TEXT_NUMBERS) {
            $complexityText = 'a-zA-Z0-9';
        } else if($complexity == self::COMPLEXITY_TEXT_NUMBERS_SPECIAL_CHARS) {
            $complexityText = 'a-zA-Z0-9!@#$%^&*()_+.,-';
        }

        $errorMessage = sprintf('Entered password does not match the following requirements:<br>Min length: %d<br>Max length: %d<br>Complexity: %s', $minLength, $maxLength, $complexityText);

        $rights = [
            'length < ' . $minLength,
            'length > ' . $maxLength,
            'value.match(/[' . $complexityText . ']/g) == null'
        ];

        $code = '
            <script type="text/javascript">
                function showError() {
                    const text = "' . $errorMessage . '";

                    $("#' . $this->name . '-form-password-complexity-check-message").html(text);

                    $("#' . $this->name . '").css("border", "1px solid red");
                }

                function hideError() {
                    $("#' . $this->name . '-form-password-complexity-check-message").html("");
                    
                    $("#' . $this->name . '").css("border", "");
                }

                $("#' . $this->name . '").on("input", function(e) {
                    const value = $("#' . $this->name . '").val();
                    const length = value.length;

                    if(' . implode(' || ', $rights) . ') {
                        showError();
                    } else {
                        hideError();
                    }
                });
            </script>
        ';

        $this->additionalCode[] = $code;

        return $this;
    }
}

?>