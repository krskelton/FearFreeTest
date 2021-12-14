<?php

add_filter('ninja_popups_subscribe_by_salesforcecase', 'ninja_popups_subscribe_by_salesforcecase', 10, 1);

function ninja_popups_subscribe_by_salesforcecase($params = array())
{
    if (snp_get_option('ml_manager') != 'salesforcecase') {
        return;
    }

    require_once SNP_DIR_PATH . '/include/httpful.phar';

    $organizationId = $params['popup_meta']['snp_ml_salesforcecase_organizationId'][0];
    if (!$organizationId) {
        $organizationId = snp_get_option('ml_salesforcecase_organizationId');
    }

    $result = array(
        'status' => false,
        'log' => array(
            'listId' => $organizationId,
            'errorMessage' => '',
        )
    );

    $data = array();
    $data['oid'] = $organizationId;
    $data['retUrl'] = '';

    if (count($params['data']['cf']) > 0) {
        foreach ($params['data']['cf'] as $k => $v) {
            $data[$k] = $v;
        }
    }

    try {
        $response = \Httpful\Request::post('https://webto.salesforce.com/servlet/servlet.WebToCase?encoding=UTF-8')
            ->body($data)
            ->send();

        $result['status'] = true;
    } catch (Exception $e) {
        $result['log']['errorMessage'] = 'Salesfore Web-To-Case error message:: ' . $e->getMessage();
    }

    return $result;
}