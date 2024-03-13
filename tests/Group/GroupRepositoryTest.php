<?php

namespace Tests\Group;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use LuckPermsAPI\Context\ContextKey;
use LuckPermsAPI\Exception\GroupNotFoundException;
use LuckPermsAPI\Group\GroupMapper;
use LuckPermsAPI\Node\NodeType;
use LuckPermsAPI\Repository\Search;
use Tests\TestCase;

class GroupRepositoryTest extends TestCase {

    public function test_all_identifiers_returns_array_of_group_names(): void {
        $httpClient = $this->createMock(Client::class);
        $httpClient->method('get')->with('/group')->willReturn(
            new Response(200, [], json_encode([
                'group1',
                'group2',
            ])),
        );

        $this->session->httpClient = $httpClient;

        $results = $this->session->groupRepository()->allIdentifiers();

        $this->assertEquals(['group1', 'group2'], $results->toArray());
    }

    public function test_search(): void {
        foreach (['key', 'keyStartsWith', 'metaKey', 'type'] as $searchMethod) {
            $httpClient = $this->createMock(Client::class);
            $httpClient->expects($this->once())->method('get')->with(
                '/group/search',
                [
                    'search' => [
                        $searchMethod => $searchMethod === 'type' ? 'inheritance' : 'hahaha.',
                    ],
                ],
            )->willReturn(
                new Response(200, [], json_encode([
                ])),
            );

            $this->session->httpClient = $httpClient;

            $method = "with{$searchMethod}";
            $this->session->groupRepository()->search(
                Search::$method($searchMethod === 'type' ? NodeType::Inheritance : 'hahaha.')
            );
        }
    }

    public function test_load_will_throw_exception_if_group_not_found(): void {
        $httpClient = $this->createMock(Client::class);
        $httpClient->method('get')->with('/group/not-a-group')->willReturn(
            new Response(404),
        );

        $this->session->httpClient = $httpClient;

        $this->expectException(GroupNotFoundException::class);
        $this->expectExceptionMessage("Group with name 'not-a-group' not found");

        $this->session->groupRepository()->load('not-a-group');
    }

    public function test_load_will_return_group_if_valid(): void {
        $httpClient = $this->createMock(Client::class);
        $httpClient->method('get')->with('/group/staff')->willReturn(
            new Response(200, [], json_encode([
                'name' => 'staff',
                'displayName' => 'Staff',
                'weight' => 1,
                'metadata' => [
                    'meta' => [
                        'test' => 'test staff value',
                    ],
                ],
                'nodes' => [
                    [
                        'key' => 'group.helper',
                        'type' => 'inheritance',
                        'value' => 'true',
                        'context' => [
                            [
                                'key' => 'world',
                                'value' => 'survival',
                            ],
                        ],
                    ],
                    [
                        'key' => 'multiverse.*',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ]
                ]
            ]))
        );

        $this->session->httpClient = $httpClient;

        $group = $this->session->groupRepository()->load('staff');

        $this->assertSame('staff', $group->name());
        $this->assertSame('Staff', $group->displayName());
        $this->assertSame(1, $group->weight());
        $this->assertCount(1, $group->metaData()->meta());
        $this->assertSame('test staff value', $group->metaData()->meta()->get('test'));
        $this->assertSame([
            'meta' => [
                'test' => 'test staff value',
            ],
        ], $group->metaData()->toArray());
        $this->assertCount(2, $group->nodes());
        $this->assertSame('group.helper', $group->nodes()->first()->key());
        $this->assertSame(NodeType::Inheritance, $group->nodes()->first()->type());
        $this->assertSame('inheritance', $group->nodes()->first()->type()->value);
        $this->assertSame('true', $group->nodes()->first()->value());
        $this->assertSame(ContextKey::World, $group->nodes()->first()->contexts()->first()->key());
        $this->assertSame('survival', $group->nodes()->first()->contexts()->first()->value());
        $this->assertSame('multiverse.*', $group->nodes()->last()->key());
        $this->assertSame(NodeType::Permission, $group->nodes()->last()->type());
        $this->assertSame('permission', $group->nodes()->last()->type()->value);
        $this->assertSame('true', $group->nodes()->last()->value());
        $this->assertCount(0, $group->nodes()->last()->contexts());
    }

    public function test_load_will_not_call_api_twice(): void {
        $httpClient = $this->createMock(Client::class);
        $httpClient->expects($this->once())->method('get')->with('/group/staff')->willReturn(
            new Response(200, [], json_encode([
                'name' => 'staff',
                'displayName' => 'Staff',
                'weight' => 1,
                'metadata' => [
                    'meta' => [
                        'test' => 'test staff value',
                    ],
                ],
                'nodes' => [
                    [
                        'key' => 'group.helper',
                        'type' => 'inheritance',
                        'value' => 'true',
                        'context' => [
                            [
                                'key' => 'world',
                                'value' => 'survival',
                            ],
                        ],
                    ],
                    [
                        'key' => 'multiverse.*',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ]
                ]
            ])),
        );

        $this->session->httpClient = $httpClient;

        // expect GroupMapper->map to be called only once, since it'll be cached the second time
        $groupMapperMock = $this->createMock(GroupMapper::class);
        $this->container->singleton(GroupMapper::class, fn() => $groupMapperMock);

        $groupMapperMock->expects($this->once())->method('map');

        $this->session->groupRepository()->load('staff');
        $this->session->groupRepository()->load('staff');
    }
}
