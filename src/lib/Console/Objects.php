<?php

declare(strict_types=1);

/**
 * tubee.io
 *
 * @copyright   Copryright (c) 2017-2018 gyselroth GmbH (https://gyselroth.com)
 * @license     GPL-3.0 https://opensource.org/licenses/GPL-3.0
 */

namespace Tubee\Console;

use GetOpt\GetOpt;
use InvalidArgumentException;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Yaml\Yaml;

class Objects extends AbstractObjects
{
    /**
     * Set options.
     */
    public static function getOptions(): array
    {
        return [
            ['f', 'filter', GetOpt::OPTIONAL_ARGUMENT, 'Specify an object filter (comma separated), the default is all objects', null],
            ['s', 'simulate', GetOpt::NO_ARGUMENT, 'Simulate operations (Readonly mode), the default is to excuete operations.', false],
            ['i', 'ignore', GetOpt::NO_ARGUMENT, 'Ignore any occured errors, the default is to abort on error.', false],
            ['E', 'export', GetOpt::NO_ARGUMENT, 'Execute export to destination endpoints.', false],
            ['e', 'endpoints', GetOpt::REQUIRED_ARGUMENT, 'Specify endpoints.', null],
            ['m', 'meta', GetOpt::NO_ARGUMENT, 'Request meta data as well', null],
            ['V', 'version', GetOpt::REQUIRED_ARGUMENT, 'Request meta data as well', null],
            ['a', 'async', GetOpt::NO_ARGUMENT, 'Schedule task, the default is to run in forderground.', false],
            ['I', 'task-interval', GetOpt::REQUIRED_ARGUMENT, 'Schedule task, the default is to run in forderground.', null],
            ['A', 'task-at', GetOpt::REQUIRED_ARGUMENT, 'Schedule task, the default is to run in forderground.', null],
            ['R', 'task-retry', GetOpt::REQUIRED_ARGUMENT, 'Schedule task, the default is to run in forderground.', null],
            ['T', 'task-retry-interval', GetOpt::REQUIRED_ARGUMENT, 'Schedule task, the default is to run in forderground.', null],
        ];
    }

    /**
     * Get operands.
     */
    public static function getOperands(): array
    {
        return [
            \GetOpt\Operand::create('action', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('mandator', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('datatype', \GetOpt\Operand::REQUIRED),
            \GetOpt\Operand::create('id', \GetOpt\Operand::OPTIONAL),
        ];
    }

    /**
     * Echo help page.
     */
    public function help(): Objects
    {
        echo "get\n";
        echo "List and query objects\n\n";
        echo "edit\n";
        echo "Edit objects\n\n";
        echo "import\n";
        echo "Start import of objects from source endpoints\n\n";
        echo "export\n";
        echo "Export objects to destination endpoints\n\n";
        echo "add\n";
        echo "Add new object\n\n";
        echo "delete\n";
        echo "Delete object (This will remove the object completely! Only a disabled object can be deleted)\n\n";
        echo "rollback\n";
        echo "Rollback object to an earlier version\n\n";
        echo "disable\n";
        echo "Disable object (Move object into trash)\n\n";
        echo "enable\n";
        echo "Enable object (Reenable a previously disabled object)\n";
        echo "lock\n";
        echo "Lock object (A locked object can not be changed)\n\n";
        echo "unlock\n";
        echo "Unlock object (Unlock a previously locked object)\n\n";
        echo $this->getopt->getHelpText();

        return $this;
    }

    /**
     * List objects.
     */
    public function get(): Objects
    {
        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                foreach ($datatype->getAll($this->getFilter(), false) as $object) {
                    echo Yaml::dump($object->decorate($this->getAttributes()), 2, 4);
                    echo "\n";
                }
            }
        }

        return $this;
    }

    /**
     * Lock objects.
     */
    public function lock(): Objects
    {
        return $this;
    }

    /**
     * Unlock objects.
     */
    public function unlock(): Objects
    {
        return $this;
    }

    /**
     * Rollback objects to a previous version.
     */
    public function rollback(): Objects
    {
        return $this;
    }

    /**
     * Delete objects.
     */
    public function delete(): Objects
    {
        return $this;
    }

    /**
     * Enable objects.
     */
    public function enable(): Objects
    {
        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                foreach ($datatype->getAll($this->getFilter(), false) as $object) {
                    $datatype->enable($object->getId(), $this->isSimulate());
                }
            }
        }

        return $this;
    }

    /**
     * Disbable objects.
     */
    public function disable(): Objects
    {
        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                foreach ($datatype->getAll($this->getFilter(), false) as $object) {
                    $datatype->disable($object->getId(), $this->isSimulate());
                }
            }
        }

        return $this;
    }

    /**
     * Compare an objects versions.
     */
    public function diff(): Objects
    {
        $stream_v1 = tmpfile();
        $meta_v1 = stream_get_meta_data($stream_v1);
        $file_v1 = $meta_v1['uri'];
        $stream_v2 = tmpfile();
        $meta_v2 = stream_get_meta_data($stream_v2);
        $file_v2 = $meta_v2['uri'];

        $tool = $this->getDiffTool();

        $mandator = $this->getMandatorsList();
        $datatype = $this->getDataTypesList();

        if (count($mandator) !== 1) {
            throw new InvalidArgumentException('one mandator is required');
        }
        $mandator = array_shift($mandator);

        if (count($datatype) !== 1) {
            throw new InvalidArgumentException('one datatype is required');
        }
        $datatype = array_shift($datatype);

        $object_v1 = $this->mandator->getOne($mandator)->getDataType($datatype)->getOne($this->getFilter(), false);
        $object_v2 = $this->mandator->getOne($mandator)->getDataType($datatype)->getOne($this->getFilter(), false, $this->getVersion());

        fwrite($stream_v1, Yaml::dump($object_v1->decorate($this->getAttributes()), 4, 2));
        fwrite($stream_v2, Yaml::dump($object_v2->decorate($this->getAttributes()), 4, 2));
        system("$tool $file_v1 $file_v2 > `tty`");

        fclose($stream_v1);
        fclose($stream_v2);

        return $this;
    }

    /**
     * Add a new object.
     */
    public function add(): Objects
    {
        $stream = tmpfile();
        $meta_data = stream_get_meta_data($stream);
        $filename = $meta_data['uri'];
        $editor = $this->getEditor();
        $mandator = $this->getMandatorsList();
        $datatype = $this->getDataTypesList();

        if (count($mandator) > 1) {
            throw new InvalidArgumentException('only one mandator may be given');
        }
        $mandator = array_shift($mandator);

        if (count($datatype) > 1) {
            throw new InvalidArgumentException('only one datatype may be given');
        }
        $datatype = array_shift($datatype);

        $this->logger->debug('open temporary file ['.$filename.'] in editor ['.$editor.']', [
            'category' => get_class($this),
        ]);

        $object = [
            'mandator' => $mandator,
            'datatype' => $datatype,
            'data' => [],
        ];

        fwrite($stream, Yaml::dump($object, 4, 2));
        system("$editor $filename > `tty`");

        $objects = $this->readStream($stream);
        fclose($stream);
        $this->createObject($object);

        return $this;
    }

    /**
     * Edit objects.
     */
    public function edit(): Objects
    {
        $stream = tmpfile();
        $objects = [];
        $edit = [];

        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                foreach ($datatype->getAll($this->getFilter(), false) as $object) {
                    $edit[] = $object->decorate($this->getAttributes());
                    $objects[(string) $object->getId()] = $object;
                }
            }
        }

        $yaml = Yaml::dump($edit, 4, 2);
        fwrite($stream, $yaml);

        $meta_data = stream_get_meta_data($stream);
        $filename = $meta_data['uri'];
        $editor = $this->getEditor();

        $this->logger->debug('open temporary file ['.$filename.'] in editor ['.$editor.']', [
            'category' => get_class($this),
        ]);

        system("$editor $filename > `tty`");

        $update = $this->readStream($stream);
        fclose($stream);
        $this->updateObjects($objects, $update);

        return $this;
    }

    /**
     * Start export.
     */
    public function export()
    {
        if ($this->getopt->getOption('async')) {
            return $this->scheduleTask(
                self::SYNC_EXPORT,
                $this->getMandatorsList(),
                $this->getDataTypesList()
            );
        }

        $start = new UTCDateTime();

        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                $datatype->export(
                    $start,
                    $this->getFilter(),
                    $this->getEndpoints(),
                    $this->isSimulate(),
                    $this->isIgnore()
                );
            }
        }
    }

    /**
     * Start import.
     */
    public function import(): Objects
    {
        if ($this->getopt->getOption('async')) {
            return $this->scheduleTask(
                $this->getopt->getOption('export') ? self::SYNC_BIDIRECTIONAL : self::SYNC_IMPORT,
                $this->getMandatorsList(),
                $this->getDataTypesList()
            );
        }

        $start = new UTCDateTime();

        foreach ($this->mandator->getAll($this->getMandatorsList()) as $mandator) {
            foreach ($mandator->getDataTypes($this->getDataTypesList()) as $datatype) {
                $datatype->import(
                    $start,
                    $this->getFilter(),
                    $this->getEndpoints(),
                    $this->isSimulate(),
                    $this->isIgnore()
                );

                $this->exportObject($datatype, $this->getFilter());
            }
        }

        return $this;
    }
}
