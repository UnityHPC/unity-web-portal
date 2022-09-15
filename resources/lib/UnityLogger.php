<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class UnityLogger {
    private $logger;
    private $printlog;

    public function __construct($log_path, $printlog)
    {
        $this->logger = new Logger('unity-web-portal');
        $this->logger->pushHandler(new StreamHandler($log_path, Logger::INFO));
        $this->logger->pushHandler(new FirePHPHandler());
        $this->printlog = $printlog;
    }

    public function logDebug($message) {
        if ($this->printlog) {
            echo "[DEBUG] " . $message;
        }

        $this->logger->debug($message);
    }

    public function logInfo($message) {
        if ($this->printlog) {
            echo "[INFO] " . $message;
        }

        $this->logger->info($message);
    }

    public function logWarning($message) {
        if ($this->printlog) {
            echo "[WARNING] " . $message;
        }

        $this->logger->warning($message);
    }

    public function logError($message) {
        if ($this->printlog) {
            echo "[ERROR] " . $message;
        }

        $this->logger->error($message);
    }

    public function logCritical($message) {
        if ($this->printlog) {
            echo "[CRITICAL] " . $message;
        }

        $this->logger->critical($message);
    }

    public function killPortal() {
        echo "The Unity web portal has run into a critical error. Please contact support with the following timestamp: " . date('m/d/Y H:i:s', time());
        die();
    }
}