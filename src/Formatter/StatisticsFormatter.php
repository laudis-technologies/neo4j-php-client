<?php

declare(strict_types=1);

/*
 * This file is part of the Laudis Neo4j package.
 *
 * (c) Laudis technologies <http://laudis.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Laudis\Neo4j\Formatter;

use Bolt\Bolt;
use Ds\Vector;
use Laudis\Neo4j\Contracts\FormatterInterface;
use Laudis\Neo4j\Databags\StatementStatistics;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use UnexpectedValueException;

/**
 * @psalm-import-type CypherResponseSet from \Laudis\Neo4j\Contracts\FormatterInterface
 * @psalm-import-type CypherResponse from \Laudis\Neo4j\Contracts\FormatterInterface
 * @psalm-import-type BoltCypherStats from \Laudis\Neo4j\Contracts\FormatterInterface
 *
 * @implements FormatterInterface<StatementStatistics>
 */
final class StatisticsFormatter implements FormatterInterface
{
    /**
     * @param CypherResponse $response
     */
    public function formatHttpStats(array $response): StatementStatistics
    {
        if (!isset($response['stats'])) {
            throw new UnexpectedValueException('No stats found in the response set');
        }

        return new StatementStatistics(
            $response['stats']['nodes_created'] ?? 0,
            $response['stats']['nodes_deleted'] ?? 0,
            $response['stats']['relationships_created'] ?? 0,
            $response['stats']['relationships_deleted'] ?? 0,
            $response['stats']['properties_set'] ?? 0,
            $response['stats']['labels_added'] ?? 0,
            $response['stats']['labels_removed'] ?? 0,
            $response['stats']['indexes_added'] ?? 0,
            $response['stats']['indexes_removed'] ?? 0,
            $response['stats']['constraints_added'] ?? 0,
            $response['stats']['constraints_removed'] ?? 0,
            $response['stats']['contains_updates'] ?? false,
            $response['stats']['contains_system_updates'] ?? false,
            $response['stats']['system_updates'] ?? 0,
        );
    }

    /**
     * @param array{stats?: BoltCypherStats} $response
     */
    public function formatBoltStats(array $response): StatementStatistics
    {
        if (!isset($response['stats'])) {
            return new StatementStatistics();
        }

        return new StatementStatistics(
            $response['stats']['nodes-created'] ?? 0,
            $response['stats']['nodes-deleted'] ?? 0,
            $response['stats']['relationships-created'] ?? 0,
            $response['stats']['relationships-deleted'] ?? 0,
            $response['stats']['properties-set'] ?? 0,
            $response['stats']['labels-added'] ?? 0,
            $response['stats']['labels-removed'] ?? 0,
            $response['stats']['indexes-added'] ?? 0,
            $response['stats']['indexes-removed'] ?? 0,
            $response['stats']['constraints-added'] ?? 0,
            $response['stats']['constraints-removed'] ?? 0,
            $response['stats']['contains-updates'] ?? false,
            $response['stats']['contains-system-updates'] ?? false,
            $response['stats']['system-updates'] ?? 0
        );
    }

    public function formatBoltResult(array $meta, array $results, Bolt $bolt): StatementStatistics
    {
        $last = array_key_last($results);
        if (!isset($results[$last])) {
            throw new UnexpectedValueException('Empty bolt result set');
        }

        /** @var array{stats?: BoltCypherStats} */
        $response = $results[$last];

        return $this->formatBoltStats($response);
    }

    public function formatHttpResult(ResponseInterface $response, array $body): Vector
    {
        $tbr = new Vector();

        foreach ($body['results'] as $results) {
            $tbr->push($this->formatHttpStats($results));
        }

        return $tbr;
    }

    public function decorateRequest(RequestInterface $request): RequestInterface
    {
        return $request;
    }

    public function statementConfigOverride(): array
    {
        return [
            'includeStats' => true,
        ];
    }
}