<?php

namespace main\test\unit\model\project;

use main\app\model\project\ProjectModel;
use main\app\model\project\ReportProjectIssueModel;
use main\test\BaseDataProvider;

/**
 *  项目汇总表模型
 */
class TestReportProjectIssueModel extends TestBaseProjectModel
{

    public static $projectData = [];
    public static $reportProjectIssueData = [];

    public static function setUpBeforeClass()
    {
        self::$projectData = self::initProject();
        self::$reportProjectIssueData = self::initReportProjectIssueModel();
    }

    public static function tearDownAfterClass()
    {
    }

    public static function initProject($info = [])
    {
        $row = BaseDataProvider::createProject($info);
        return $row;
    }

    /**
     * @throws \Exception
     */
    public static function initReportProjectIssueModel($info = [])
    {
        $model = new ReportProjectIssueModel();
        $info['project_id'] = self::$projectData['id'];
        $info['date'] = date('Y-m-d H:i:s', time());

        list($ret, $insertId) = $model->insert($info);
        if (!$ret) {
            var_dump(__METHOD__ . '  failed,' . $insertId);
            return [];
        }
        return $model->getRowById($insertId);
    }

    /**
     * @throws \Exception
     */
    public function testGetById()
    {
        $model = new ReportProjectIssueModel();
        $ret = $model->getById(self::$reportProjectIssueData['id']);
        $this->assertTrue(is_array($ret));
    }

    /**
     * @throws \Exception
     */
    public function testGetsByProject()
    {
        $model = new ReportProjectIssueModel();
        $ret = $model->getById(self::$projectData['id']);
        $this->assertTrue(is_array($ret));
    }

    /**
     * @throws \Exception
     */
    public function testRemoveById()
    {
        $model = new ReportProjectIssueModel();
        $ret = $model->removeById(self::$projectData['id'], self::$reportProjectIssueData['id']);
        $this->assertTrue(is_numeric($ret));
    }

    /**
     * @throws \Exception
     */
    public function testRemoveByProject()
    {
        $model = new ReportProjectIssueModel();
        $ret = $model->removeByProject(self::$projectData['id']);
        $this->assertTrue(is_numeric($ret));
    }
}
