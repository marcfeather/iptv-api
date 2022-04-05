<?php

trait MoviesTrait
{
    private $streamContext;
    private $playListUrl = "https://iptv-org.github.io/iptv/%s/%s.m3u";
    private $groupUrl = "https://iptv-org.github.io/api/%s.json";
    public $cacheTime = 21600;

    public function __construct()
    {
        // prepare stream context for external calls
        $this->streamContext = stream_context_create(
            array('http' => array('timeout' => 3))
        );

        // handle pre-flight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return $this->jsonResponse([]);
        }

        // handle json type POST requests
        $input = file_get_contents("php://input");
        if (json_decode($input, true) !== null) {
            $post = json_decode($input, true);
            foreach ($post as $key => $value) {
                $_POST[$key] = $_POST[$key] ?? $value;
            }
        }
    }

    public function fetchGroup(): string
    {
        $group = json_decode(
            $this->cacheHelper(
                sprintf($this->groupUrl, $this->group),
                $this->group === "categories" ? "processCategories" : null
            ),
            true
        );

        // search
        if (!empty($this->query)) {
            $newGroup = [];
            foreach ($group as $s) {
                if (preg_match("/{$this->query}/i", $s['name'])) $newGroup[] = $s;
            }
            $group = $newGroup;
            unset($newGroup);
        }

        return $this->jsonResponse(['success' => true, 'data' => $group]);
    }

    private function processCategories(string $categories): string
    {
        $cat = json_decode($categories, true);
        foreach ($cat as &$c) {
            $c['code'] = $c['id'];
            unset($c['id']);
        }

        return json_encode($cat);
    }

    public function fetchPlayList()
    {

        $playlist = json_decode(
            $this->cacheHelper(
                sprintf(
                    $this->playListUrl,
                    strtolower($this->group),
                    strtolower($this->groupItem)
                ),
                "processPlayList"
            ),
            true
        );

        // search
        if (!empty($this->query)) {
            $newList = [];
            foreach ($playlist as $s) {
                if (preg_match("/{$this->query}/i", $s['title'])) $newList[] = $s;
            }
            $playlist = $newList;
            unset($newList);
        }

        return $this->jsonResponse(['success' => true, 'data' => $playlist]);
    }

    private function processPlayList(string $playlist): string
    {
        // remove Geo-blocked streams
        $pieces = explode("\n#EXTINF", $playlist);
        array_shift($pieces);
        $streams = [];

        foreach ($pieces as $piece) {

            if (preg_match("/Geo-blocked/i", $piece)) continue;

            $stream = [];
            $lines = explode("\n", trim($piece));

            $tvgId = explode('tvg-id="', $lines['0']);
            $stream['tvgId'] = substr($tvgId[1], 0, strpos($tvgId[1], '"'));

            $tvgCountry = explode('tvg-country="', $lines['0']);
            $stream['tvgCountry'] = substr($tvgCountry[1], 0, strpos($tvgCountry[1], '"'));

            $tvgLanguage = explode('tvg-language="', $lines['0']);
            $stream['tvgLanguage'] = substr($tvgLanguage[1], 0, strpos($tvgLanguage[1], '"'));

            $tvgLogo = explode('tvg-logo="', $lines['0']);
            $stream['tvgLogo'] = substr($tvgLogo[1], 0, strpos($tvgLogo[1], '"'));

            $groupTitle = explode('group-title="', $lines['0']);
            $stream['groupTitle'] = substr($groupTitle[1], 0, strpos($groupTitle[1], '"'));

            $title = explode(',', $groupTitle[1]);
            $stream['title'] = explode("\n", $title[count($title) - 1])[0];

            $stream['url'] = array_pop($lines);
            $streams[] = $stream;
        }
        $result = json_encode($streams);

        unset($piece, $pieces);

        return $result;
    }

    private function cacheHelper(string $url, string $callback = null): string
    {
        $response = "";

        $cacheDir = dirname(__FILE__) . "/cache/";
        if (!file_exists($cacheDir)) mkdir($cacheDir, 0777, true);

        $filename = $cacheDir . $this->slugify($url);

        if (file_exists($filename) && time() - $this->cacheTime < filemtime($filename)) {
            $response = file_get_contents($filename);
        } else {
            $response = file_get_contents($url, false, $this->streamContext);
            if (!empty($callback)) $response = $this->$callback($response);
            file_put_contents($filename, $response);
        }

        return $response;
    }

    private function slugify(string $text): string
    {
        $slug = strtolower($text);
        $slug = trim(preg_replace('/[^A-Za-z0-9]+/', '-', $slug), '-');
        return $slug;
    }

    public function jsonResponse($data, int $code = 200)
    {

        http_response_code($code);
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Cache-Control: no-cache');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS');
        header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization, Access-Control-Allow-Methods, Cache-Control');

        echo (gettype($data) === "string") ? $data : json_encode($data);

        exit();
    }
}
