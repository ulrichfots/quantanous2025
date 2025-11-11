<?php
/**
 * Chargement manuel d'un fichier .env local (compatible PHP 7.4+).
 * Chaque ligne valide doit suivre le format CLE=VALEUR.
 * Les lignes vides ou commençant par # sont ignorées.
 */

if (!defined('ENV_FILE_LOADED')) {
    define('ENV_FILE_LOADED', true);

    $startsWith = static function (string $value, string $prefix): bool {
        return strncmp($value, $prefix, strlen($prefix)) === 0;
    };

    $endsWith = static function (string $value, string $suffix): bool {
        if ($suffix === '') {
            return true;
        }
        $suffixLength = strlen($suffix);
        return substr($value, -$suffixLength) === $suffix;
    };

    $envPath = __DIR__ . '/.env';

    if (is_readable($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || $startsWith($trimmed, '#')) {
                continue;
            }

            $delimiterPos = strpos($line, '=');
            if ($delimiterPos === false) {
                continue;
            }

            $key = trim(substr($line, 0, $delimiterPos));
            $value = trim(substr($line, $delimiterPos + 1));

            // Supprimer les guillemets enveloppants
            if (($startsWith($value, '"') && $endsWith($value, '"')) ||
                ($startsWith($value, "'") && $endsWith($value, "'"))) {
                $value = substr($value, 1, -1);
            }

            if ($key !== '') {
                putenv("{$key}={$value}");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
