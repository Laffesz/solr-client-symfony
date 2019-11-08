<?php

declare(strict_types=1);

/*
 * This file is part of Solr Client Symfony package.
 *
 * (c) ingatlan.com Zrt. <fejlesztes@ingatlan.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace iCom\SolrClient\Tests\Query;

use iCom\SolrClient\Query\Command\Add;
use iCom\SolrClient\Query\Command\Commit;
use iCom\SolrClient\Query\Command\Delete;
use iCom\SolrClient\Query\Command\Optimize;
use iCom\SolrClient\Query\SelectQuery;
use iCom\SolrClient\Query\UpdateQuery;
use PHPUnit\Framework\TestCase;

/**
 * @covers \iCom\SolrClient\Query\UpdateQuery
 */
final class UpdateQueryTest extends TestCase
{
    /** @test */
    public function it_is_possible_to_have_multiple_commands_with_same_key(): void
    {
        $commands = UpdateQuery::create([new Add(['id' => 1])]);
        $commands->add(Add::create(['id' => 2])->commitWithin(1000)->disableOverWrite());
        $commands->add(Add::create(['id' => 3])->commitWithin(500));
        $commands->delete(Delete::fromIds(['1', '2', '3']));
        $commands->delete(Delete::fromQuery(SelectQuery::create()->query('id:"1"')));

        $this->assertSame('{"add":{"doc":{"id":1}},"add":{"doc":{"id":2},"commitWithin":1000,"overwrite":false},"add":{"doc":{"id":3},"commitWithin":500},"delete":["1","2","3"],"delete":{"query":"id:\"1\""}}', $commands->toSolrJson());
    }

    /** @test */
    public function it_converts_optimize_and_commit_to_json_objects(): void
    {
        $commands = UpdateQuery::create();
        $commands->commit(new Commit());
        $commands->optimize(new Optimize());

        $this->assertSame('{"commit":{},"optimize":{}}', $commands->toSolrJson());
    }
}
