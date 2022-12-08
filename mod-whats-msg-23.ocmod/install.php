<?php
$this->load->model('user/user_group');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'extension/module/whatsappmsg');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'extension/module/whatsappmsg');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'access', 'marketing/wcontact');
$this->model_user_user_group->addPermission($this->user->getGroupId(), 'modify', 'marketing/wcontact');