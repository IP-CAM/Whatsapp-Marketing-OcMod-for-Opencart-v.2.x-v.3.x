<?php
class ControllerMarketingWcontact extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('marketing/wcontact');

		$this->document->setTitle($this->language->get('heading_title'));

		$data['user_token'] = $this->session->data['user_token'];

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('marketing/wcontact', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['cancel'] = $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true);

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		$this->load->model('customer/customer_group');

		$data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('marketing/wcontact', $data));
	}

	public function send() {
		$this->load->language('marketing/wcontact');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (!$this->user->hasPermission('modify', 'marketing/wcontact')) {
				$json['error']['warning'] = $this->language->get('error_permission');
			}

			if (!$this->request->post['message'] || (strlen($this->request->post['message']) < 12)) {
				$json['error']['message'] = $this->language->get('error_message');
			}

			if (!$json) {
				$this->load->model('setting/store');
				$this->load->model('setting/setting');
				$this->load->model('customer/customer');
				$this->load->model('sale/order');

				$store_info = $this->model_setting_store->getStore($this->request->post['store_id']);

				if ($store_info) {
					$store_name = $store_info['name'];
				} else {
					$store_name = $this->config->get('config_name');
				}

				$setting = $this->model_setting_setting->getSetting('config', $this->request->post['store_id']);

				$store_email = isset($setting['config_email']) ? $setting['config_email'] : $this->config->get('config_email');

				if (isset($this->request->get['page'])) {
					$page = (int)$this->request->get['page'];
				} else {
					$page = 1;
				}

				$email_total = 0;

				$emails = array();

				switch ($this->request->post['to']) {
					case 'newsletter':
						$customer_data = array(
							'filter_newsletter' => 1,
							'start'             => ($page - 1) * 10,
							'limit'             => 10
						);

						$email_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$emails[] = preg_replace('/[^0-9]/', '',$result['telephone']);
						}
						break;
					case 'customer_all':
						$customer_data = array(
							'start' => ($page - 1) * 10,
							'limit' => 10
						);

						$email_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$emails[] = preg_replace('/[^0-9]/', '',$result['telephone']);
						}
						break;
					case 'customer_group':
						$customer_data = array(
							'filter_customer_group_id' => $this->request->post['customer_group_id'],
							'start'                    => ($page - 1) * 10,
							'limit'                    => 10
						);

						$email_total = $this->model_customer_customer->getTotalCustomers($customer_data);

						$results = $this->model_customer_customer->getCustomers($customer_data);

						foreach ($results as $result) {
							$emails[$result['customer_id']] = preg_replace('/[^0-9]/', '',$result['telephone']);
						}
						break;
					case 'customer':
						if (!empty($this->request->post['customer'])) {
							$customers = array_slice($this->request->post['customer'], ($page - 1) * 10, 10);

							foreach ($customers as $customer_id) {
								$customer_info = $this->model_customer_customer->getCustomer($customer_id);

								if ($customer_info) {
									$emails[] = preg_replace('/[^0-9]/', '',$customer_info['telephone']);
								}
							}

							$email_total = count($emails);
						}
						break;
					case 'affiliate_all':
						$affiliate_data = array(
							'filter_affiliate' => 1,
							'start'            => ($page - 1) * 10,
							'limit'            => 10
						);

						$email_total = $this->model_customer_customer->getTotalCustomers($affiliate_data);

						$results = $this->model_customer_customer->getCustomers($affiliate_data);

						foreach ($results as $result) {
							$emails[] = preg_replace('/[^0-9]/', '',$result['telephone']);
						}
						break;
					case 'affiliate':
						if (!empty($this->request->post['affiliate'])) {
							$affiliates = array_slice($this->request->post['affiliate'], ($page - 1) * 10, 10);

							foreach ($affiliates as $affiliate_id) {
								$affiliate_info = $this->model_customer_customer->getCustomer($affiliate_id);

								if ($affiliate_info) {
									$emails[] = preg_replace('/[^0-9]/', '',$affiliate_info['telephone']);
								}
							}

							$email_total = count($this->request->post['affiliate']);
						}
						break;
					case 'product':
						if (isset($this->request->post['product'])) {
							$email_total = $this->model_sale_order->getTotalEmailsByProductsOrderedAlt($this->request->post['product']);

							$results = $this->model_sale_order->getEmailsByProductsOrderedAlt($this->request->post['product'], ($page - 1) * 10, 10);

							foreach ($results as $result) {
								$emails[] = preg_replace('/[^0-9]/', '',$result['telephone']);
							}
						}
						break;
				}

				if ($emails) {
					$json['success'] = $this->language->get('text_success');

					$start = ($page - 1) * 10;
					$end = $start + 10;

					if($page == 1 && $email_total < 10) {
						$json['success'] = sprintf($this->language->get('text_sent'), $email_total, $email_total);
					} else if($page == 1 && $email_total > 10) {
						$json['success'] = sprintf($this->language->get('text_sent'), 10, $email_total);
					} else if($page > 1 && $email_total < ($page * 10)) {
						$json['success'] = sprintf($this->language->get('text_sent'), $email_total, $email_total);
					} else {
						$json['success'] = sprintf($this->language->get('text_sent'), ($start * $page), $email_total);
					}

					if ($end < $email_total) {
						$json['next'] = str_replace('&amp;', '&', $this->url->link('marketing/wcontact/send', 'user_token=' . $this->session->data['user_token'] . '&page=' . ($page + 1), true));
					} else {
						$json['next'] = '';
					}

					$message = html_entity_decode($this->request->post['message'], ENT_QUOTES, 'UTF-8');

					foreach ($emails as $email) {
						$this->postMsg($email, $message);
					}
				} else {
					$json['error']['email'] = $this->language->get('error_email');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
	
    public function postMsg($telephone, $message) {
    
  		$url = $this->config->get('module_whatsmastermsg_link');
  		if ($this->config->get('module_whatsmastermsg_ver')) {
  		$json_convert  = array('token' => $this->config->get('module_whatsmastermsg_token'), 'phone' => $this->config->get('module_whatsmastermsg_ddi').$telephone, 'id' => $telephone, 'message' => $message);
  		} else {
  		$json_convert  = array('token' => $this->config->get('module_whatsmastermsg_token'), 'numero' => $this->config->get('module_whatsmastermsg_ddi').$telephone, 'message' => $message);	  
  		}

  		$soap_do = curl_init();
  		curl_setopt($soap_do, CURLOPT_URL, $url);
  		curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
  		curl_setopt($soap_do, CURLOPT_TIMEOUT,        10);
  		curl_setopt($soap_do, CURLOPT_CUSTOMREQUEST, "POST");
  		curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true );
  		curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
  		curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
  		curl_setopt($soap_do, CURLOPT_POST,           true );
  		curl_setopt($soap_do, CURLOPT_POSTFIELDS,     $json_convert);
  
  		$response = curl_exec($soap_do); 
		curl_close($soap_do);
  		$resposta = json_decode($response, true);
  		return  $response;
   }
}
