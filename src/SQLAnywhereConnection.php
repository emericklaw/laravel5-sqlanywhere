<?php

namespace josueneo\laravel5sqlanywhere;

use Illuminate\Database\Connection;
use josueneo\laravel5sqlanywhere\SQLAnywhereQueryGrammar as QueryGrammar;
use josueneo\laravel5sqlanywhere\SQLAnywhereProcessor as Processor;

class SQLAnywhereConnection extends Connection
{

    /**
     * Run a select statement against the database.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {

        // Replaces named parameters with ? due to Pdo not supporting named parameters being used multiple times in a query
        if (count($bindings) > 0) {
            if (str_contains(array_key_first($bindings), ':')) {
                $newParams = [];
                foreach ($bindings as $key => $value) {
                    $key = str_replace(':', '', $key);
                    $result = preg_match_all("/:{$key}\b/", $query, $matches, PREG_OFFSET_CAPTURE);

                    if ($result === false) throw new \Exception("Unexpected result from preg_match. Expected 1, but got $result");

                    foreach ($matches[0] as $match) {
                        $newParams[(int) $match[1]] = $value;
                    }
                }
                ksort($newParams, SORT_NUMERIC);
                $newParams = array_values($newParams);

                // Replace named parameters with ? in $sql
                $newSql = preg_replace("/:[a-zA-Z0-9_]+/", '?', $query, -1, $count);
                if ($newSql === null) throw new \Exception('Error when executing preg_replace');
                if ($count !== count($newParams)) throw new \Exception("Number of placeholders not same as number of params");

                // Replace arguments with results
                $query = $newSql;
                $bindings = $newParams;
            }
        }

        return $this->run($query, $bindings, function ($query, $bindings) use ($useReadPdo) {
            if ($this->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->prepared(
                $this->getPdoForSelect($useReadPdo)->prepare($query)
            );

            $this->bindValues($statement, $this->prepareBindings($bindings));

            $statement->execute();

            return $statement->fetchAll();
        });
    }

    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new SQLAnywhereQueryGrammar);
    }
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SQLAnywhereSchemaGrammar);
    }
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }
}
