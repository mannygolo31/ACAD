<?php
/**
 * File-based rate limiter for login and API endpoints.
 * Limits: 5 attempts per 15 minutes per IP for login routes.
 * General endpoints: 60 requests per minute per IP.
 */

class RateLimiter {
    private $storage_dir;
    private $max_attempts;
    private $window_seconds;

    public function __construct($max_attempts = 5, $window_seconds = 900, $storage_dir = null) {
        $this->max_attempts = $max_attempts;
        $this->window_seconds = $window_seconds;
        $this->storage_dir = $storage_dir ?: sys_get_temp_dir() . '/rate_limiter';

        if (!is_dir($this->storage_dir)) {
            mkdir($this->storage_dir, 0700, true);
        }
    }

    private function getFilePath($key) {
        return $this->storage_dir . '/' . md5($key) . '.json';
    }

    private function loadAttempts($key) {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return [];
        }
        $data = json_decode(file_get_contents($file), true);
        if (!is_array($data)) {
            return [];
        }
        $now = time();
        return array_filter($data, function ($timestamp) use ($now) {
            return ($now - $timestamp) < $this->window_seconds;
        });
    }

    private function saveAttempts($key, $attempts) {
        $file = $this->getFilePath($key);
        file_put_contents($file, json_encode(array_values($attempts)), LOCK_EX);
    }

    public function isRateLimited($identifier) {
        $attempts = $this->loadAttempts($identifier);
        return count($attempts) >= $this->max_attempts;
    }

    public function recordAttempt($identifier) {
        $attempts = $this->loadAttempts($identifier);
        $attempts[] = time();
        $this->saveAttempts($identifier, $attempts);
    }

    public function getRemainingAttempts($identifier) {
        $attempts = $this->loadAttempts($identifier);
        $remaining = $this->max_attempts - count($attempts);
        return max(0, $remaining);
    }

    public function getRetryAfter($identifier) {
        $attempts = $this->loadAttempts($identifier);
        if (count($attempts) < $this->max_attempts) {
            return 0;
        }
        $oldest = min($attempts);
        return max(0, $this->window_seconds - (time() - $oldest));
    }

    public function reset($identifier) {
        $file = $this->getFilePath($identifier);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function cleanup() {
        $files = glob($this->storage_dir . '/*.json');
        $now = time();
        foreach ($files as $file) {
            if (($now - filemtime($file)) > $this->window_seconds * 2) {
                unlink($file);
            }
        }
    }
}

function checkRateLimit($endpoint = 'general') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = $endpoint . ':' . $ip;

    if ($endpoint === 'login') {
        $limiter = new RateLimiter(5, 900); // 5 attempts per 15 minutes
    } else {
        $limiter = new RateLimiter(60, 60); // 60 requests per minute
    }

    if ($limiter->isRateLimited($key)) {
        $retry_after = $limiter->getRetryAfter($key);
        http_response_code(429);
        $minutes = ceil($retry_after / 60);
        echo json_encode([
            'error' => 'Too many requests. Please try again in ' . $minutes . ' minute(s).',
            'retry_after' => $retry_after
        ]);
        exit();
    }

    return $limiter;
}

function recordLoginAttempt($success = false) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $key = 'login:' . $ip;
    $limiter = new RateLimiter(5, 900);

    if (!$success) {
        $limiter->recordAttempt($key);
    } else {
        $limiter->reset($key);
    }

    return $limiter->getRemainingAttempts($key);
}
?>
