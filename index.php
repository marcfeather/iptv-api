<?php

require_once __DIR__ . '/MoviesTrait.php';

final class Index
{

    use MoviesTrait;

    public function __construct()
    {

        $output = array(
            'success' => true,
            'message' => 'Collection of publicly available IPTV channels from all over the world',
            'author' => 'Samuel',
            'endpoints' => array(
                '/api.php' => array(
                    'method' => 'GET|POST',
                    'description' => 'Fetch list of IPTV groups and Playlists.',
                    'parameters' => array(
                        array(
                            'name' => 'group',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'The group to be returned (categories, languages, countries).',
                            'default' => 'categories'
                        ),
                        array(
                            'name' => 'code',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'The code from the group. Note: You must specify "group" if you want to specify "code".',
                            'default' => null
                        ),
                        array(
                            'name' => 'query',
                            'type' => 'string',
                            'required' => false,
                            'description' => 'Search term to filter results. Note: If only "group" is specified, then this field will apply to the playlist (group) title, but if "code" is specified, this will apply to the playlist items titles.',
                            'default' => null
                        )
                    )
                )
            )
        );

        $this->jsonResponse($output);
    }
}

new Index();
