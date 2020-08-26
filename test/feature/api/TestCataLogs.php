<?php


namespace main\test\featrue\api;

use main\test\BaseApiTestCase;

class TestCataLogs extends BaseApiTestCase
{
    public static $clean = [];

    /**
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
    }

    /**
     * @throws \Exception
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    /**
     * @param string $name
     * @return array
     */
    public function addCataLog($name = '这个分类')
    {
        $accessToken = '';
        $projectId = self::$projectId;
        $client = new \GuzzleHttp\Client();
        $url = ROOT_URL . 'api/cata_logs/v1/?access_token=' . $accessToken . '&project_id=' . $projectId;

        $response = $client->post($url, [
            'form_params' => [
                'name' => $name . quickRandomStr(50),
                'label_id_arr' => [1,2],
                'description' => '描述1：' . quickRandomStr(),
            ]
        ]);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $newId = $respArr['data']['body']['id'];

        return [
            'id' => $newId,
            'body' => $respArr['data']['body'],
            'resp' => $respArr
        ];
    }

    /**
     * 添加分类
     * @throws \Exception
     */
    public function testPost()
    {
        $ret = $this->addCataLog();
        $respArr = $ret['resp'];
        $this->assertNotEmpty($respArr, '接口请求失败');
        $this->assertTrue(isset($respArr['data']), '不包含data属性');
        $this->assertEquals('200', $respArr['ret']);
        $respData = $respArr['data'];
        $this->assertNotEmpty($respData);

    }

    /**
     * @throws \Exception
     */
    public function testGet()
    {
        $accessToken = '';
        $projectId = self::$projectId;

        $client = new \GuzzleHttp\Client();
        $url = ROOT_URL . 'api/labels/v1/?access_token=' . $accessToken . '&project_id=' . $projectId;
        $response = $client->post($url, [
            'form_params' => [
                'label_name' => '这个标签' . quickRandomStr(50),
                'description' => '描述1：' . quickRandomStr(),
            ]
        ]);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $newLabelId = $respArr['data']['body']['id'];


        $url = ROOT_URL . 'api/labels/v1/' . $newLabelId . '?access_token=' . $accessToken;
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $this->assertNotEmpty($respArr, '接口请求失败');
        $this->assertTrue(isset($respArr['data']), '不包含data属性');
        $this->assertEquals('200', $respArr['ret']);
        $respData = $respArr['data'];
        $this->assertNotEmpty($respData);
        if(strpos($respData['body']['title'],'这个标签') !== false){
            $this->assertTrue(true); //包含
        }else{
            $this->assertTrue(false);
        }

        $url = ROOT_URL . 'api/labels/v1/?access_token=' . $accessToken . '&project_id=' . $projectId;
        $client = new \GuzzleHttp\Client();
        $response = $client->get($url);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $this->assertNotEmpty($respArr, '接口请求失败');
        $this->assertTrue(isset($respArr['data']), '不包含data属性');
        $this->assertEquals('200', $respArr['ret']);
        $respData = $respArr['data'];
        $this->assertNotEmpty($respData);

    }

    /**
     *
     * @throws \Exception
     */
    public function testDelete()
    {
        $projectId = self::$projectId;
        $accessToken = '';


        $client = new \GuzzleHttp\Client();
        $url = ROOT_URL . 'api/labels/v1/?access_token=' . $accessToken . '&project_id=' . $projectId;
        $response = $client->post($url, [
            'form_params' => [
                'label_name' => '这个标签' . quickRandomStr(50),
                'description' => '描述1：' . quickRandomStr(),
            ]
        ]);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $newLabelId = $respArr['data']['body']['id'];


        $url = ROOT_URL . 'api/labels/v1/' . $newLabelId . '?access_token=' . $accessToken;
        $response = $client->delete($url);
        $rawResponse = $response->getBody()->getContents();
        $respArr = json_decode($rawResponse, true);
        $this->assertNotEmpty($respArr, '接口请求失败');
    }
}