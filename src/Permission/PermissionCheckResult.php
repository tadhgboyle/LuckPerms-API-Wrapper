<?php

namespace LuckPermsAPI\Permission;

use LuckPermsAPI\Node\Node;

class PermissionCheckResult
{
    private bool $result;
    private ?Node $node;

    public function __construct(bool $result, ?Node $node)
    {
        $this->result = $result;
        $this->node = $node;
    }

    public function result(): bool
    {
        return $this->result;
    }

    public function node(): Node
    {
        return $this->node;
    }
}
