<?php

require_once __DIR__ . '/MoviesTrait.php';

final class Api
{
    use MoviesTrait;
    protected $group = 'categories';
    protected $groupItem;
    protected $query;

    public function __construct()
    {
        $group = $_POST['group'] ?? $_GET['group'] ?? null;
        $code = $_POST['code'] ?? $_GET['code'] ?? null;
        $query = $_POST['query'] ?? $_GET['query'] ?? null;
        if (!empty($group)) $this->group = $group;
        if (!empty($code)) $this->groupItem = $code;
        if (!empty($query)) $this->query = $query;

        if (empty($this->groupItem)) $this->fetchGroup();
        else $this->fetchPlayList();
    }
}

$M = new Api();
