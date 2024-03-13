<?php

namespace Tests\Permission;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use LuckPermsAPI\Context\Context;
use LuckPermsAPI\Context\ContextKey;
use LuckPermsAPI\QueryOptions\QueryFlag;
use LuckPermsAPI\QueryOptions\QueryMode;
use LuckPermsAPI\QueryOptions\QueryOptions;
use Tests\TestCase;

class PermissionCheckTest extends TestCase {

    public function test_has_permission_will_return_permission_check_result_for_user(): void {
        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->withConsecutive(
            ['/user/9490b898-856a-4aae-8de3-2986d007269b'],
            [
                '/user/9490b898-856a-4aae-8de3-2986d007269b/permissionCheck',
                [
                    'json' => [
                        'uniqueId' => '9490b898-856a-4aae-8de3-2986d007269b',
                        'permission' => 'minecraft.command.ban',
                    ],
                ]
            ]
        )->willReturnOnConsecutiveCalls(
            new Response(200, [], json_encode([
                'uniqueId' => '9490b898-856a-4aae-8de3-2986d007269b',
                'username' => 'Aberdeener',
                'nodes' => [
                    [
                        'key' => 'minecraft.command.ban',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ],
                ],
                'metadata' => [
                    'meta' => [],
                    'prefix' => '',
                    'suffix' => '',
                    'primaryGroup' => 'default',
                ],
            ])),
            new Response(200, [], json_encode([
                'result' => 'true',
                'node' => [
                    'key' => 'minecraft.command.ban',
                    'type' => 'permission',
                    'value' => true,
                    'context' => [],
                ],
            ]))
        );

        $this->session->httpClient = $mockClient;

        $user = $this->session->userRepository()->load('9490b898-856a-4aae-8de3-2986d007269b');

        $permissionCheckResult = $user->hasPermission('minecraft.command.ban');

        $this->assertTrue($permissionCheckResult->result());
        $node = $permissionCheckResult->node();
        $this->assertEquals('minecraft.command.ban', $node->key());
    }

    public function test_has_permission_will_return_permission_check_result_for_group(): void {
        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->withConsecutive(
            ['/group/default'],
            [
                '/group/default/permissionCheck',
                [
                    'json' => [
                        'name' => 'default',
                        'permission' => 'minecraft.command.ban',
                    ],
                ]
            ]
        )->willReturnOnConsecutiveCalls(
            new Response(200, [], json_encode([
                'name' => 'default',
                'displayName' => 'Default',
                'weight' => 1,
                'metadata' => [
                    'meta' => [],
                ],
                'nodes' => [
                    [
                        'key' => 'minecraft.command.ban',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ],
                ]
            ])),
            new Response(200, [], json_encode([
                'result' => 'true',
                'node' => [
                    'key' => 'minecraft.command.ban',
                    'type' => 'permission',
                    'value' => true,
                    'context' => [],
                ],
            ]))
        );

        $this->session->httpClient = $mockClient;

        $group = $this->session->groupRepository()->load('default');

        $permissionCheckResult = $group->hasPermission('minecraft.command.ban');

        $this->assertTrue($permissionCheckResult->result());
        $node = $permissionCheckResult->node();
        $this->assertEquals('minecraft.command.ban', $node->key());
    }

    public function test_permission_check_with_many_query_options(): void {
        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->with('/group/default')->willReturn(
            new Response(200, [], json_encode([
                'name' => 'default',
                'displayName' => 'Default',
                'weight' => 1,
                'metadata' => [
                    'meta' => [],
                ],
                'nodes' => [
                    [
                        'key' => 'minecraft.command.ban',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ],
                ]
            ])),
        );

        $mockClient->method('post')->with(
            '/group/default/permissionCheck',
            [
                'json' => [
                    'name' => 'default',
                    'permission' => 'minecraft.command.ban',
                    'queryOptions' => [
                        'mode' => 'contextual',
                        'flags' => [
                            'resolve_inheritance',
                            'include_nodes_without_server_context',
                        ],
                        'contexts' => [
                            [
                                'key' => 'world',
                                'value' => 'lobby'
                            ],
                            [
                                'key' => 'gamemode',
                                'value' => 'creative',
                            ],
                        ],
                    ],
                ],
            ],
        )->willReturn(new Response(200, [], json_encode([
            'result' => 'true',
            'node' => [
                'key' => 'minecraft.command.ban',
                'type' => 'permission',
                'value' => true,
                'context' => [],
            ],
        ])));

        $this->session->httpClient = $mockClient;

        $group = $this->session->groupRepository()->load('default');

        $permissionCheckResult = $group->hasPermission(
            'minecraft.command.ban',
            QueryOptions::make()
                ->setMode(QueryMode::Contextual)
                ->withFlag(QueryFlag::ResolveInheritance)
                ->withFlag(QueryFlag::IncludeNodesWithoutServerContext)
                ->withContext(new Context(ContextKey::World, 'lobby'))
                ->withContext(new Context(ContextKey::GameMode, 'creative')),
        );

        $this->assertTrue($permissionCheckResult->result());
        $node = $permissionCheckResult->node();
        $this->assertEquals('minecraft.command.ban', $node->key());
    }

    public function test_permission_check_with_some_query_options(): void {
        $mockClient = $this->createMock(Client::class);

        $mockClient->method('get')->with('/group/default')->willReturn(
            new Response(200, [], json_encode([
                'name' => 'default',
                'displayName' => 'Default',
                'weight' => 1,
                'metadata' => [
                    'meta' => [],
                ],
                'nodes' => [
                    [
                        'key' => 'minecraft.command.ban',
                        'type' => 'permission',
                        'value' => 'true',
                        'context' => [],
                    ],
                ]
            ])),
        );

        $mockClient->method('post')->with(
            '/group/default/permissionCheck',
            [
                'json' => [
                    'name' => 'default',
                    'permission' => 'minecraft.command.ban',
                    'queryOptions' => [
                        'mode' => 'contextual',
                        'flags' => [
                            'resolve_inheritance',
                        ],
                    ],
                ],
            ],
        )->willReturn(new Response(200, [], json_encode([
            'result' => 'true',
            'node' => [
                'key' => 'minecraft.command.ban',
                'type' => 'permission',
                'value' => true,
                'context' => [],
            ],
        ])));

        $this->session->httpClient = $mockClient;

        $group = $this->session->groupRepository()->load('default');

        $permissionCheckResult = $group->hasPermission(
            'minecraft.command.ban',
            QueryOptions::make()
                ->setMode(QueryMode::Contextual)
                ->withFlag(QueryFlag::ResolveInheritance),
        );

        $this->assertTrue($permissionCheckResult->result());
        $node = $permissionCheckResult->node();
        $this->assertEquals('minecraft.command.ban', $node->key());
    }


}
