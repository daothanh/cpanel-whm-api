<?php

namespace PreviewTechs\cPanelWHM\WHM;


use Http\Client\Exception;
use PreviewTechs\cPanelWHM\Entity\Account;
use PreviewTechs\cPanelWHM\Exceptions\ClientExceptions;
use PreviewTechs\cPanelWHM\WHMClient;

class Accounts
{
    /**
     * @var WHMClient
     */
    protected $client;

    public function __construct(WHMClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param null $keyword
     * @param null $searchType
     * @param array $options
     *
     * @return array
     * @throws ClientExceptions
     * @throws Exception
     */
    public function searchAccounts($keyword = null, $searchType = null, array $options = [])
    {
        $limit = 10;
        $page  = 1;

        $params = [
            'api.version'      => 1,
            'api.chunk.enable' => 1,
            'api.chunk.size'   => $limit,
            'api.chunk.start'  => $page * $limit
        ];

        if ( ! empty($options['limit'])) {
            $params['api.chunk.size'] = intval($options['limit']);
        }

        if ( ! empty($options['page'])) {
            $params['api.chunk.start'] = intval($options['page']) * $params['api.chunk.size'];
        }

        if ( ! empty($searchType) && ! in_array($searchType, ["domain", "owner", "user", "ip", "package"])) {
            throw new \InvalidArgumentException("`searchType` must be one of these - domain, owner, user, ip, package");
        }

        if ( ! empty($options['searchmethod']) && ! in_array($options['searchmethod'], ["exact", "regex"])) {
            throw new \InvalidArgumentException("options[searchmethod] must be either `regex` or `exact`");
        }

        if ( ! empty($options['want'])) {
            $params['want'] = $options['want'];
        }

        if ( ! empty($searchType)) {
            $params['searchtype'] = $searchType;
        }

        if ( ! empty($keyword)) {
            $params['search'] = $keyword;
            empty($searchType) ? $params['searchtype'] = "user" : null;
        }

        $results = $this->client->sendRequest("/json-api/listaccts", "GET", $params);
        if (empty($results['data']['acct'])) {
            return [];
        }

        $accounts = [];
        foreach ($results['data']['acct'] as $account) {
            $accounts[] = Account::buildFromArray($account);
        }

        return ['accounts' => $accounts, 'count' => $params['api.chunk.size'], 'page' => $page];
    }
}