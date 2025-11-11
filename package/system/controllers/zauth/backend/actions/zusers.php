<?php

class actionZauthZusers extends cmsAction {

    public function run($action = false,$id = false) {

        if ($this->request->isAjax()) {

            $grid = $this->loadDataGrid('zusers', false, 'zusers');
            $filter = array();
            $filter_str = $this->request->get('filter', '');
            $filter_str = cmsUser::getUPSActual('zusers', $filter_str);
            if ($filter_str) {
                parse_str($filter_str, $filter);
                $this->model->applyGridFilter($grid, $filter);
            }
            $total = $this->model->getCount('zusers');
            $perpage = isset($filter['perpage']) ? $filter['perpage'] : admin::perpage;
            $pages = ceil($total / $perpage);
            $this->model->setPerPage($perpage);
            $users = $this->model->joinUser('user_id',array(
                'nickname' => 'nickname',
                'email' => 'email'
            ))->get('zusers');
            $template = cmsTemplate::getInstance();
            $template->renderGridRowsJSON($grid, $users, $total, $pages);
            $this->halt();
        }

        //удаление привязки
        if($action == 'delete'){
            $csrf_token = $this->request->get('csrf_token', '');
            if (!cmsForm::validateCSRFToken($csrf_token)) {
                return cmsCore::error404();
            }
            $this->model->delete('zusers',$id);
            cmsUser::addSessionMessage(LANG_DELETE_SUCCESS);
            $this->redirectToAction('zusers');
        }

        $grid = $this->loadDataGrid('zusers');

                return $this->cms_template->render('backend/zusers', array(
                    'grid' => $grid
        ));
    }

}
