<?php

namespace main\test\unit\model\issue;

use main\app\model\agile\AgileBoardColumnModel;
use main\app\model\agile\AgileBoardModel;
use main\test\unit\BaseUnitTranTestCase;

/**
 *  AgileBoardColumnModel 测试类
 * User: sven
 */
class TestAgileBoardColumnModel extends BaseUnitTranTestCase
{

    /**
     * project 数据
     * @var array
     */
    public static $board = [];

    public static $insertIdArr = [];

    /**
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        self::$board = self::initBoard();
    }


    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
    }

    /**
     * 初始化项目
     * @return array
     * @throws \Exception
     */
    public static function initBoard()
    {
        $name = 'board-' . mt_rand(12345678, 92345678);

        // 表单数据 $post_data
        $postData = [];
        $postData['name'] = $name;
        $postData['project_id'] = 0;
        $postData['weight'] = 1;

        $model = new AgileBoardModel();
        list($ret, $insertId) = $model->insert($postData);
        if (!$ret) {
            parent::fail(__CLASS__.'/initBoard  failed,' . $insertId);
            return [];
        }
        $row = $model->getRowById($insertId);
        return $row;
    }

    /**
     * 清除数据
     */
    public static function clearData()
    {
        $model = new AgileBoardModel();
        $model->deleteById(self::$board['id']);

        if (!empty(self::$insertIdArr)) {
            $model = new AgileBoardColumnModel();
            foreach (self::$insertIdArr as $id) {
                $model->deleteById($id);
            }
        }
    }

    /**
     * 主流程
     */
    public function testMain()
    {
        $boardId = self::$board['id'];
        $model = new AgileBoardColumnModel();
        // 1. 新增测试需要的数据
        $addNum = 3;
        for ($i = 0; $i < $addNum; $i++) {
            $info = [];
            $info['name'] = 'test-name' . $i;
            $info['board_id'] = $boardId;
            $info['weight'] = $i;
            $info['data'] = '["resolved","closed","done"]';
            list($ret, $insertId) = $model->insert($info);
            $this->assertTrue($ret, $insertId);
            if ($ret) {
                self::$insertIdArr[] = $insertId;
            }
        }

        // 2.测试 getById
        $row = $model->getById($insertId);
        foreach ($info as $key => $val) {
            $this->assertEquals($val, $row[$key]);
        }

        // 3.测试 getByName
        $row = $model->getByName($info['name']);
        foreach ($info as $key => $val) {
            $this->assertEquals($val, $row[$key]);
        }

        // 2.测试 getItemsByWorkflowId
        $rows = $model->getsByBoard($boardId);
        $this->assertNotEmpty($rows);
        $this->assertCount($addNum, $rows);

        // 5.删除
        $ret = (bool)$model->deleteByBoardId($boardId);
        $this->assertTrue($ret);
    }
}
