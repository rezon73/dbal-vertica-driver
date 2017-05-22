<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\DBAL\Vertica;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DBAL Driver for {@link http://www.vertica.com/ Vertica}
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 */
class VerticaDriver implements Driver
{
    /**
     * @var OptionsResolver
     */
    private $resolver;
    /**
     * @var OptionsResolver
     */
    private $driverResolver;

    /**
     * VerticaDriver constructor.
     */
    public function __construct()
    {
        $this->resolver = $this->configureResolver(new OptionsResolver());
        $this->driverResolver = $this->configureDriverResolver(new OptionsResolver());
    }

    /**
     * Attempts to create a connection with the database.
     *
     * @param array $params All connection parameters passed by the user.
     *                                  - dsn: ODBC dsn, if provided all other parameters are ignored
     *                                  - driver: ODBC Driver name, default to Vertica
     *                                  - host: server host
     *                                  - port: server port
     *                                  - dbname: database name
     * @param string $username The username to use when connecting.
     * @param string $password The password to use when connecting.
     * @param array $driverOptions The driver options to use when connecting.
     *
     * @return Driver\Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = [])
    {
        return new ODBCConnection($this->constructDsn($params, $driverOptions), $username, $password);
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabasePlatform()
    {
        return new VerticaPlatform();
    }

    /**
     * {@inheritDoc}
     */
    public function getSchemaManager(Connection $conn)
    {
        return new VerticaSchemaManager($conn);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'vertica';
    }

    /**
     * {@inheritDoc}
     */
    public function getDatabase(Connection $conn)
    {
        $params = $conn->getParams();

        if (isset($params['dbname'])) {
            return $params['dbname'];
        }

        return $conn->query('SELECT CURRENT_DATABASE()')->fetchColumn();
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function configureResolver(OptionsResolver $resolver)
    {
        $resolver
            ->setDefined(['dsn'])
            ->setDefaults(['host' => 'localhost', 'port' => 5433, 'dbname' => 'vmartdb'])
            ->setAllowedTypes('dsn', 'string')
            ->setAllowedTypes('host', 'string')
            ->setAllowedTypes('port', 'integer')
            ->setAllowedTypes('dbname', 'string');

        return $resolver;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @return OptionsResolver
     */
    protected function configureDriverResolver(OptionsResolver $resolver)
    {
        $booleanNormalizer = function (Options $options, $value) {
            return ($value === true) ? 'true' : 'false';
        };
        $resolver
            ->setDefined(
                [
                    'odbc_driver',
                    'connection_load_balance',
                    'backup_server_nodes',
                    'schema',
                    'connection_settings',
                    'label',
                    'audo_commit',
                    'direct_batch_insert',
                    'locale',
                    'read_only',
                    'result_buffer_size',
                ]
            )
            ->setDefault('odbc_driver', 'Vertica')
            ->setAllowedTypes('odbc_driver', 'string')
            ->setAllowedTypes('connection_load_balance', 'string')
            ->setAllowedTypes('backup_server_nodes', 'string')
            ->setAllowedTypes('schema', 'string')
            ->setAllowedTypes('connection_settings', 'string')
            ->setAllowedTypes('label', 'string')
            ->setAllowedTypes('audo_commit', 'boolean')
            ->setAllowedTypes('direct_batch_insert', 'boolean')
            ->setAllowedTypes('read_only', 'boolean')
            ->setAllowedTypes('locale', 'string')
            ->setAllowedTypes('result_buffer_size', 'integer')
            ->setNormalizer('audo_commit', $booleanNormalizer)
            ->setNormalizer('direct_batch_insert', $booleanNormalizer)
            ->setNormalizer('read_only', $booleanNormalizer);

        return $resolver;
    }

    /**
     * @param array $params
     * @param array $driverOptions
     *
     * @return string
     */
    protected function constructDsn(array $params, array $driverOptions)
    {
        $params = $this->resolver->resolve(
            array_intersect_key($params, array_flip($this->resolver->getDefinedOptions()))
        );
        if (!empty($params['dsn'])) {
            return $params['dsn'];
        }
        $driverOptions = $this->driverResolver->resolve($driverOptions);
        $dsn = 'Driver=' . $driverOptions['odbc_driver'] . ';';

        $dsn .= 'Servername=' . $params['host'] . ';';
        $dsn .= 'Port=' . $params['port'] . ';';
        $dsn .= 'Database=' . $params['dbname'] . ';';

        if (!empty($driverOptions['connection_load_balance'])) {
            $dsn .= 'ConnectionLoadBalance=' . $driverOptions['connection_load_balance'] . ';';
        }

        if (!empty($driverOptions['backup_server_nodes'])) {
            $dsn .= 'BackupServerNode=' . $driverOptions['backup_server_nodes'] . ';';
        }

        $connectionSettings = [];
        if (!empty($driverOptions['schema'])) {
            $connectionSettings[] = sprintf('SET search_path=\'%s\'', $driverOptions['schema']);
        }
        if (!empty($driverOptions['connection_settings'])) {
            $connectionSettings[] = $driverOptions['connection_settings'];
        }

        if (!empty($connectionSettings)) {
            $dsn .= sprintf(
                "ConnSettings=%s;",
                str_replace([';', ' '], ['%3B', '+'], implode('%3B', $connectionSettings))
            );
        }

        if (!empty($driverOptions['label'])) {
            $dsn .= 'Label=' . $driverOptions['label'] . ';';
        }

        if (isset($driverOptions['audo_commit'])) {
            $dsn .= 'AutoCommit=' . ($driverOptions['audo_commit'] ? 'true' : 'false') . ';';
        }
        if (isset($driverOptions['direct_batch_insert'])) {
            $dsn .= 'DirectBatchInsert=' . ($driverOptions['direct_batch_insert'] ? 'true' : 'false') . ';';
        }
        if (isset($driverOptions['locale'])) {
            $dsn .= 'Locale=' . $driverOptions['locale'] . ';';
        }
        if (isset($driverOptions['read_only'])) {
            $dsn .= 'ReadOnly=' . ($driverOptions['read_only'] ? 'true' : 'false') . ';';
        }
        if (isset($driverOptions['result_buffer_size'])) {
            $dsn .= 'ResultBufferSize=' . $driverOptions['result_buffer_size'] . ';';
        }

        return $dsn;
    }
}
