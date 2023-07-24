<?php
namespace tool;

use Elasticsearch\ClientBuilder;

class Elasticsearch
{
    /**
     * 创建索引
     * @param $index
     * @param $param
     * @return array
     */
    public function createESIndex($index, $param)
    {
        $client = $this->getClient();

        $params = [
            'index' => $index,
            'body' => [
                'mappings' => [
                    $index => [
                        'properties' => $param
                    ]
                ]
            ]
        ];

        $response = $client->indices()->create($params);
        return $response;
    }

    /**
     * 删除索引
     * @param $index
     * @return array
     */
    public function deleteESIndex($index)
    {
        $client = $this->getClient();

        $params = ['index' => $index];
        $response = $client->indices()->delete($params);

        return $response;
    }

    /**
     * 索引文档
     * @param $index
     * @param $id
     * @param $param
     * @return array
     */
    public function createDocument($index, $id, $param)
    {
        $client = $this->getClient();

        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id,
            'body' => $param
        ];

        $response = $client->index($params);
        return $response;
    }

    /**
     * 更新文档
     * @param $index
     * @param $id
     * @param $param
     * @return array
     */
    public function updateDocument($index, $id, $param)
    {
        $client = $this->getClient();

        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id,
            'body' => [
                'doc' => $param
            ]
        ];

        $response = $client->update($params);
        return $response;
    }

    /**
     * 删除索引
     * @param $index
     * @param $id
     * @return array
     */
    public function deleteDocument($index, $id)
    {
        $client = $this->getClient();

        $params = [
            'index' => $index,
            'type' => $index,
            'id' => $id
        ];

        $response = $client->delete($params);
        return $response;
    }

    /**
     * es查询
     * @param $index
     * @param $param
     * @return array
     */
    public function search($index, $param)
    {
        $client = $this->getClient();

        $params = [
            'index' => $index,
            'type' => $index,
            'body' => [
                'query' => [
                    'match' => $param
                ]
            ]
        ];

        $results = $client->search($params);
        return array_chunk($results['hits']['hits'], config('robot.default_think_tips'));
    }

    /**
     * 创建es 客户端
     * @return \Elasticsearch\Client
     */
    private function getClient()
    {
        $hosts = config('robot.es_host');
        return ClientBuilder::create()->setHosts($hosts)->build();
    }
}