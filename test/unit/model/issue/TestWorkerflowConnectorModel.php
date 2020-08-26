<?php

namespace main\test\unit\model\issue;

use main\app\model\issue\WorkflowConnectorModel;
use main\app\model\issue\WorkflowModel;

/**
 *  WorkerflowConnectorModel.php 测试类
 * User: sven
 */
class TestWorkerflowConnectorModel extends TestBaseIssueModel
{

    /**
     *  workflow 数据
     * @var array
     */
    public static $workflow = [];

    public static $insertIdArr = [];

    /**
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        self::$workflow = self::initWorkflow();
    }

    /**
     * 确保生成的测试数据被清除
     */
    public static function tearDownAfterClass()
    {
    }

    /**
     * 初始化项目
     * @return array
     * @throws \Exception
     */
    public static function initWorkflow()
    {
        // 表单数据 $post_data
        $info = [];
        $info['name'] = 'test-name-' . mt_rand(11111, 999999);
        $info['create_uid'] = 1;
        $info['steps'] = 0;
        $info['data'] = '{}';
        $info['is_system'] = 0;
        $model = new WorkflowModel();
        list($ret, $insertId) = $model->add($info);
        if (!$ret) {
            parent::fail(__CLASS__.'/initWorkflow  failed,' . $insertId);
            return [];
        }
        $row = $model->getRowById($insertId);
        return $row;
    }


    /**
     * 主流程
     */
    public function testMain()
    {
        // 1.新增数据
        $workflowId = self::$workflow['id'];
        $info = [];
        $info['connector_id'] = '1';
        $info['title'] = 'test-title';
        $info['source_id'] = '2';
        $info['target_id'] = '6';
        $model = new WorkflowConnectorModel();
        list($ret, $insertId) = $model->add($workflowId, $info);
        $this->assertTrue($ret, $insertId);
        if ($ret) {
            self::$insertIdArr[] = $insertId;
        }
        $row = $model->getRowById($insertId);
        foreach ($info as $key => $item) {
            $this->assertEquals($item, $row[$key]);
        }

        // 2.测试 getItemsByWorkflowId
        $rows = $model->getItemsByWorkflowId($workflowId);
        $this->assertNotEmpty($rows);
        $this->assertCount(1, $rows);

        // 3.测试 deleteByWorkflowId
        $deleteCount = (int) $model->deleteByWorkflowId($workflowId);
        $this->assertEquals(1, $deleteCount);
        $rows = $model->getItemsByWorkflowId($workflowId);
        $this->assertEmpty($rows);
    }
}
