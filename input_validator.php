<?php
/**
 * Input validation and sanitization utilities.
 * Rejects oversized or malformed data.
 */

class InputValidator {
    private static $maxLengths = [
        'username'          => 50,
        'password'          => 128,
        'first_name'        => 100,
        'middle_name'       => 100,
        'last_name'         => 100,
        'nickname'          => 50,
        'email'             => 254,
        'mobile_number'     => 20,
        'telephone_number'  => 20,
        'position'          => 100,
        'department'        => 100,
        'branch'            => 100,
        'company'           => 150,
        'religion'          => 100,
        'civil_status'      => 20,
        'gender'            => 10,
        'employment_status' => 50,
        'pay_frequency'     => 50,
        'sss_no'            => 20,
        'tin'               => 20,
        'pagibig_mid'       => 20,
        'philhealth_no'     => 20,
        'place_of_birth'    => 200,
        'present_address'   => 500,
        'permanent_address' => 500,
        'spouse_name'       => 100,
        'spouse_occupation' => 100,
        'father_name'       => 100,
        'father_occupation' => 100,
        'mother_name'       => 100,
        'mother_occupation' => 100,
        'relative_working'  => 100,
        'relative_details'  => 500,
        'emergency_name'    => 100,
        'emergency_address' => 500,
        'emergency_contact' => 20,
        'search'            => 200,
        'default'           => 255,
    ];

    public static function validateLength($field, $value) {
        $max = isset(self::$maxLengths[$field]) ? self::$maxLengths[$field] : self::$maxLengths['default'];
        if (strlen($value) > $max) {
            return false;
        }
        return true;
    }

    public static function sanitizeString($value, $field = 'default') {
        if (!is_string($value)) {
            return '';
        }
        $value = trim($value);
        if (!self::validateLength($field, $value)) {
            return false;
        }
        $value = strip_tags($value);
        return $value;
    }

    public static function validateEmail($email) {
        if (empty($email)) return true;
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function validateDate($date) {
        if (empty($date)) return true;
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function validateNumeric($value) {
        if ($value === '' || $value === null) return true;
        return is_numeric($value);
    }

    public static function validateInteger($value) {
        if ($value === '' || $value === null) return true;
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    public static function validatePhone($phone) {
        if (empty($phone)) return true;
        return preg_match('/^[0-9+\-\(\)\s]{0,20}$/', $phone) === 1;
    }

    public static function validateGender($gender) {
        return in_array($gender, ['Male', 'Female', 'Other']);
    }

    public static function validateCivilStatus($status) {
        if (empty($status)) return true;
        return in_array($status, ['Single', 'Married', 'Divorced', 'Widowed', '']);
    }

    public static function validateEmploymentStatus($status) {
        if (empty($status)) return true;
        return in_array($status, ['Full-time', 'Part-time', 'Contractual', 'Probationary', 'Regular', '']);
    }

    public static function validateFileUpload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
        $errors = [];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error.';
            return $errors;
        }

        if ($file['size'] > $max_size) {
            $errors[] = 'File size exceeds ' . ($max_size / 1048576) . 'MB limit.';
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed_types)) {
            $errors[] = 'Invalid file type. Allowed: ' . implode(', ', $allowed_types);
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        $allowed_mimes = [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
        ];
        if (!in_array($mime, $allowed_mimes)) {
            $errors[] = 'File content does not match its extension.';
        }

        return $errors;
    }

    public static function validateAllInputs($post_data, $required_fields = []) {
        $errors = [];

        foreach ($required_fields as $field) {
            if (!isset($post_data[$field]) || trim($post_data[$field]) === '') {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

        foreach ($post_data as $field => $value) {
            if (is_string($value) && !self::validateLength($field, $value)) {
                $max = isset(self::$maxLengths[$field]) ? self::$maxLengths[$field] : self::$maxLengths['default'];
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " exceeds maximum length of $max characters.";
            }
        }

        if (isset($post_data['email']) && !empty($post_data['email'])) {
            if (!self::validateEmail($post_data['email'])) {
                $errors[] = 'Invalid email address format.';
            }
        }

        if (isset($post_data['date_of_birth']) && !empty($post_data['date_of_birth'])) {
            if (!self::validateDate($post_data['date_of_birth'])) {
                $errors[] = 'Invalid date of birth format (expected YYYY-MM-DD).';
            }
        }

        if (isset($post_data['date_hired']) && !empty($post_data['date_hired'])) {
            if (!self::validateDate($post_data['date_hired'])) {
                $errors[] = 'Invalid date hired format (expected YYYY-MM-DD).';
            }
        }

        $phone_fields = ['mobile_number', 'telephone_number', 'emergency_contact'];
        foreach ($phone_fields as $field) {
            if (isset($post_data[$field]) && !empty($post_data[$field])) {
                if (!self::validatePhone($post_data[$field])) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' contains invalid characters.';
                }
            }
        }

        $numeric_fields = ['basic_salary', 'rate', 'allowances'];
        foreach ($numeric_fields as $field) {
            if (isset($post_data[$field]) && !empty($post_data[$field])) {
                if (!self::validateNumeric($post_data[$field])) {
                    $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' must be a valid number.';
                }
            }
        }

        if (isset($post_data['number_of_siblings']) && !empty($post_data['number_of_siblings'])) {
            if (!self::validateInteger($post_data['number_of_siblings'])) {
                $errors[] = 'Number of siblings must be a whole number.';
            }
        }

        return $errors;
    }

    public static function rejectOversizedRequest($max_post_size = 10485760) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $content_length = isset($_SERVER['CONTENT_LENGTH']) ? (int)$_SERVER['CONTENT_LENGTH'] : 0;
            if ($content_length > $max_post_size) {
                http_response_code(413);
                die('Request body too large. Maximum size: ' . ($max_post_size / 1048576) . 'MB');
            }
        }
    }
}
?>
