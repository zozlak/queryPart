<?php

/*
 * The MIT License
 *
 * Copyright 2020 zozlak.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace zozlak\queryPart;

use PDO;
use PDOStatement;
use RuntimeException;
use Psr\Log\LoggerInterface;

/**
 * Simple container for SQL query and its param
 *
 * @author zozlak
 */
class QueryPart implements \Stringable {

    static private int $n = 0;
    public string $query;
    public LoggerInterface | null $log;

    /**
     *
     * @var array<mixed>
     */
    public array $param;

    /**
     * Optional list of query columns
     * 
     * @var array<string>
     */
    public array $columns;

    /**
     * 
     * @param array<mixed> $param
     * @param array<string> $columns
     */
    public function __construct(string $query = '', array $param = [],
                                array $columns = [],
                                ?LoggerInterface $log = null) {
        $this->query   = $query;
        $this->param   = $param;
        $this->columns = $columns;
        $this->log     = $log;
    }

    public function __toString(): string {
        $query = $this->query;
        $pos   = 0;
        foreach ($this->param as $i) {
            $pos = strpos($query, '?', $pos);
            if ($pos === false) {
                throw new RuntimeException('More parameters than placeholders');
            }
            $query = substr_replace($query, "'$i'", $pos, 1);
            $pos   += strlen($i) + 2;
        }
        $pos = strpos($query, '?', $pos);
        if ($pos !== false) {
            throw new RuntimeException('More placeholders than parameters');
        }
        return $query;
    }

    public function execute(PDO $pdo): PDOStatement {
        $this->log?->info((string) $this);
        $t     = microtime(true);
        $query = $pdo->prepare($this->query);
        $query->execute($this->param);
        $this->log?->info('Execution time ' . (microtime(true) - $t));
        return $query;
    }

    /**
     * Pastes the join code and the query if the query is not empty.
     * 
     * If the query is empty, returns an empty string.
     * 
     * @param string $type left side of the join clause, e.g. `LEFT JOIN`
     * @param string $clause right side of the join clause, e.g. `USING(id)`
     */
    public function join(string $type, string $clause): string {
        if (empty($this->query)) {
            return '';
        }
        self::$n++;
        return $type . " (" . $this->query . ") _t" . self::$n . " " . $clause;
    }
}
