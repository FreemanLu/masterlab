<?php

namespace main\app\ctrl\project;

use main\app\classes\LogOperatingLogic;
use main\app\classes\ProjectListCountLogic;
use main\app\classes\ProjectLogic;
use main\app\classes\IssueTypeLogic;
use main\app\classes\SettingsLogic;
use main\app\classes\UserAuth;
use main\app\classes\UserLogic;
use main\app\ctrl\BaseUserCtrl;
use main\app\ctrl\Org;
use main\app\event\CommonPlacedEvent;
use main\app\event\Events;
use main\app\model\OrgModel;
use main\app\model\project\ProjectIssueTypeSchemeDataModel;
use main\app\model\project\ProjectMainExtraModel;
use main\app\model\project\ProjectModel;
use main\app\model\project\ProjectVersionModel;
use main\app\model\project\ProjectModuleModel;
use main\app\model\user\UserModel;

/**
 * Class Setting
 * @package main\app\ctrl\project
 */
class Setting extends BaseUserCtrl
{
    public function __construct()
    {
        parent::__construct();
        parent::addGVar('top_menu_active', 'project');
    }

    /**
     * 修改项目信息
     * @throws \Exception
     */
    public function saveSettingsProfile()
    {
        $isUpdateLeader = false;
        $projectParamkey = ProjectLogic::PROJECT_GET_PARAM_ID;
        if (isPost()) {
            $params = $_POST['params'];
            $uid = $this->getCurrentUid();
            $projectModel = new ProjectModel($uid);
            $preData = $projectModel->getRowById($_GET[$projectParamkey]);
            $projectIssueTypeSchemeDataModel = new ProjectIssueTypeSchemeDataModel();


            $settingLogic = new SettingsLogic();
            $maxLengthProjectName = $settingLogic->maxLengthProjectName();

            if (!isset($params['name'])) {
                $this->ajaxFailed('表单错误', '需要填写项目名称');
            }
            if (isset($params['name']) && empty(trimStr($params['name']))) {
                $this->ajaxFailed('表单错误', '需要填写项目名称。');
            }
            if (isset($params['name']) && strlen($params['name']) > $maxLengthProjectName) {
                $this->ajaxFailed('表单错误', '名称长度太长,长度应该小于'. $maxLengthProjectName);
            }
            if (isset($params['name']) && $projectModel->checkNameExist($params['name'])) {
                $this->ajaxFailed('表单错误', '项目名称已经被使用了,请更换一个吧');
            }


            if (isset($params['type']) && empty(trimStr($params['type']))) {
                $this->ajaxFailed('param_error:type_is_null');
            }

            $params['type'] = intval($params['type']);

            if (!isset($params['lead']) || empty($params['lead'])) {
                $params['lead'] = $uid;
            }

            $info = [];
            $info['name'] = $params['name'];
            // 修改项目leader
            if ($preData['lead'] != $params['lead']) {
                $info['lead'] = $params['lead'];
                $isUpdateLeader = true;
            }
            $info['description'] = $params['description'];
            $info['type'] = $params['type'];
            $info['category'] = 0;
            $info['url'] = $params['url'];
            $info['avatar'] = !empty($params['avatar_relate_path']) ? $params['avatar_relate_path'] : '';
            //$info['detail'] = $params['detail'];
            $info['workflow_scheme_id'] = isset($params['workflow_scheme_id'])?$params['workflow_scheme_id']:1;
            $issueTypeSchemeId = $params['issue_type_scheme_id'];

            // 管理员可以变更项目所属的组织
            if (isset($params['org_id']) && $preData['org_id'] != $params['org_id'] && $this->isAdmin) {
                $orgModel = new OrgModel();
                $orgInfo = $orgModel->getById($params['org_id']);
                $info['org_id'] = $params['org_id'];
                $info['org_path'] = $orgInfo['path'];
            }

            $projectModel->db->beginTransaction();

            $ret1 = $projectModel->update($info, array('id' => $_GET[$projectParamkey]));

            if ($isUpdateLeader) {
                $retModifyLeader = ProjectLogic::assignAdminRoleForProjectLeader($_GET[$projectParamkey], $info['lead']);
                if (!$retModifyLeader[0]) {
                    $projectModel->db->rollBack();
                    $this->ajaxFailed('错误', '更新项目负责人失败');
                }
            }

            $projectMainExtra = new ProjectMainExtraModel();
            if ($projectMainExtra->getByProjectId($_GET[$projectParamkey])) {
                $ret3 = $projectMainExtra->updateByProjectId(array('detail' => $params['detail']), $_GET[$projectParamkey]);
            } else {
                $ret3 = $projectMainExtra->insert(array('project_id' => $_GET[$projectParamkey], 'detail' => $params['detail']));
            }
            if (!$ret3[0]) {
                $projectModel->db->rollBack();
                $this->ajaxFailed('错误', '更新项目描述失败');
            }
            // @todo 先判断项目类型有变更，再去做更新
            if ($preData['type'] != $params['type']) {
                $schemeId = ProjectLogic::getIssueTypeSchemeId($params['type']);
                $retSchemeId = $projectIssueTypeSchemeDataModel->getSchemeId($_GET[$projectParamkey]);
                if ($retSchemeId) {
                    $ret2 = $projectIssueTypeSchemeDataModel->update(array('issue_type_scheme_id' => $schemeId), array('project_id' => $_GET[$projectParamkey]));
                } else {
                    $ret2 = $projectIssueTypeSchemeDataModel->insert(array('issue_type_scheme_id' => $schemeId, 'project_id' => $_GET[$projectParamkey]));
                }
                if (!$ret2[0]) {
                    $projectModel->db->rollBack();
                    $this->ajaxFailed('错误', '更新项目类型失败');
                }
            }

            if (!isset($info['type'])
                || !is_numeric($info['type'])
                || !in_array($info['type'], ProjectLogic::$type_all)
            ) {
                $this->ajaxFailed('参数错误', '项目类型错误');
            }

            // 保存项目事项类型方案
            $projectId = $_GET[ProjectLogic::PROJECT_GET_PARAM_ID];
            $projectIssueTypeSchemeDataModel = new ProjectIssueTypeSchemeDataModel();
            $projectIssueTypeSchemeData = $projectIssueTypeSchemeDataModel->getRow('*', ['project_id' => $projectId]);

            if ($projectIssueTypeSchemeData) {
                $rowId = $projectIssueTypeSchemeData['id'];
                $updates = ['issue_type_scheme_id' => $issueTypeSchemeId];
                $projectIssueTypeSchemeDataModel->update($updates, ['id' => $rowId]);
            } else {
                $new = ['issue_type_scheme_id' => $issueTypeSchemeId, 'project_id' => $projectId];
                $projectIssueTypeSchemeDataModel->insert($new);
            }

            if ($ret1[0]) {
                $projectModel->db->commit();

                //写入操作日志
                $logData = [];
                $logData['user_name'] = $this->auth->getUser()['username'];
                $logData['real_name'] = $this->auth->getUser()['display_name'];
                $logData['obj_id'] = 0;
                $logData['module'] = LogOperatingLogic::MODULE_NAME_PROJECT;
                $logData['page'] = $_SERVER['REQUEST_URI'];
                $logData['action'] = LogOperatingLogic::ACT_EDIT;
                $logData['remark'] = '修改项目信息';
                $logData['pre_data'] = $preData;
                $logData['cur_data'] = $info;
                LogOperatingLogic::add($uid, $_GET[ProjectLogic::PROJECT_GET_PARAM_ID], $logData);
                // 分发事件
                $info['id'] = $projectId;
                $event = new CommonPlacedEvent($this, ['pre_data'=>$preData,'cur_data'=>$info]);
                $this->dispatcher->dispatch($event,  Events::onProjectUpdate);
                $this->ajaxSuccess("success");
            } else {
                $projectModel->db->rollBack();
                $this->ajaxFailed('错误', '更新数据失败');
            }
        } else {
            $this->ajaxFailed('错误', '请求方式ERR');
        }
    }

    public function updateProjectKey()
    {
        if (isPost()) {
            $params = $_POST['params'];
            $uid = $this->getCurrentUid();
            $projectModel = new ProjectModel($uid);

            if (!isset($params['key']) || !isset($params['new_key'])) {
                $this->ajaxFailed('param_error:need key name');
            }

            $params['new_key'] = trim($params['new_key']);
            if ($params['key'] == $params['new_key']) {
                $this->ajaxFailed('param_error:key repetition');
            }

            $isNotKey = $projectModel->checkIdKeyExist($_GET[ProjectLogic::PROJECT_GET_PARAM_ID], $params['new_key']);
            if ($isNotKey) {
                $this->ajaxFailed('param_error:KEY Exist.');
            }

            $info = [];
            $info['key'] = $params['new_key'];
            $ret = $projectModel->update($info, array("id" => $_GET[ProjectLogic::PROJECT_GET_PARAM_ID]));

            if ($ret[0]) {
                $this->ajaxSuccess("success");
            } else {
                $this->ajaxFailed('错误', '更新数据失败,详情:' . $ret[1]);
            }
        } else {
            $this->ajaxFailed('错误', '请求方式ERR');
        }
    }


    /**
     * @throws \Exception
     */
    public function issueType()
    {
        $projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;
        $logic = new IssueTypeLogic();
        $data['issue_types'] = $logic->getIssueType($projectId);
        $this->ajaxSuccess('success', $data);

        $this->ajaxSuccess('ok', $data);
    }
}
