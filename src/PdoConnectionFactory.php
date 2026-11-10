<?php
namespace MvcLite;

use MvcLite\Interfaces\IPdoConnectionFactory;

final class PdoConnectionFactory implements IPdoConnectionFactory
{
    /** @var array<string, \PDO> */
    private array $connections = [];

    public function __construct(
        private CConfig $config,
        private CSetting $settings
    ) {
    }

    public function connect(string $database = ''): \PDO
    {
        $databaseConfig = $this->databaseConfig($database);
        $dsn = is_array($databaseConfig) ? (string) ($databaseConfig['dsn'] ?? '') : (string) $databaseConfig;
        if ($dsn === '' && $database !== '') {
            $defaultDatabaseConfig = $this->config->_get('db', []);
            $dsn = is_array($defaultDatabaseConfig) ? (string) ($defaultDatabaseConfig['dsn'] ?? '') : '';
            if ($dsn === '') {
                throw new \RuntimeException("No PDO DSN is configured for database '$database'.");
            }
            $databaseConfig = $defaultDatabaseConfig;
        }
        if ($dsn === '') {
            throw new \RuntimeException('No PDO DSN is configured for the default database.');
        }

        $username = is_array($databaseConfig) ? (string) ($databaseConfig['username'] ?? '') : '';
        $password = is_array($databaseConfig) ? (string) ($databaseConfig['password'] ?? '') : '';
        $key = hash('sha256', $dsn . "\0" . $username . "\0" . $password);

        if (!isset($this->connections[$key])) {
            $this->connections[$key] = new \PDO($dsn, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            ]);
        }

        return $this->connections[$key];
    }

    /**
     * @return array<string, mixed>|string
     */
    private function databaseConfig(string $database): array|string
    {
        if ($database !== '') {
            $config = $this->config->_get($database, []);
            return is_array($config) || is_string($config) ? $config : [];
        }

        $defaultDatabase = (string) $this->settings->_get(
            'dbenv',
            (string) $this->config->_get('dbenv', '')
        );
        if ($defaultDatabase !== '') {
            $config = $this->config->_get($defaultDatabase, []);
            if ((is_array($config) && !empty($config['dsn'])) || (is_string($config) && $config !== '')) {
                return $config;
            }
        }

        $config = $this->config->_get('db', []);
        return is_array($config) || is_string($config) ? $config : [];
    }
}
