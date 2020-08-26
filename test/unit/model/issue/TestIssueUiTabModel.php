<?php

namespace main\test\unit\model\issue;

use main\app\model\issue\IssueTypeModel;
use main\app\model\issue\IssueUiTabModel;
use main\app\model\project\ProjectModel;
use main\test\BaseDataProvider;

/**
 *  IssueUiTabModel.php 测试类
 * User: sven
 */
class TestIssueUiTabModel extends TestBaseIssueModel
{

    public static $insertIdArr = [];

    /**
     * @throws \Exception
     */
    public static function setUpBeforeClass()
    {
    }

    /**
     * 确保生成的测试数据被清除
     */
    public static function tearDownAfterClass()
    {
    }


    /**
     * 主流程
     *
     */
    public function testMain()
    {
        // 1.测试默认的ui配置
        $issueBugTypeId = IssueTypeModel::getInstance()->getIdByKey('bug');
        $uiType = 'test-type';
        $tabName = 'test-tab';
        $model = new IssueUiTabModel();

        // 2.测试插入
        list($ret, $insertId) = $model->add($issueBugTypeId, 1, $tabName, $uiType);
        $this->assertTrue($ret, $insertId);
        if ($ret) {
            self::$insertIdArr[] = $insertId;
        }
        $createUIConfigs = $model->getItemsByIssueTypeIdType($issueBugTypeId, $uiType);
        $this->assertNotEmpty($createUIConfigs);
        $this->assertCount(1, $createUIConfigs);

        $deleteCount = (int) $model->deleteByIssueType($issueBugTypeId, $uiType);
        $this->assertEquals(1, $deleteCount);
        // 5.删除
        $ret = $model->deleteById($insertId);
    }
}
