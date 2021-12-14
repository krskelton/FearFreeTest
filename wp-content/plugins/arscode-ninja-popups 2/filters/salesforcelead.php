<?php

add_filter('ninja_popups_subscribe_by_salesforcelead', 'ninja_popups_subscribe_by_salesforcelead', 10, 1);

function ninja_popups_subscribe_by_salesforcelead($params = array())
{
    if (snp_get_option('ml_manager') != 'salesforcelead') {
        return;
    }

    require_once SNP_DIR_PATH . '/include/httpful.phar';

    $organizationId = $params['popup_meta']['snp_ml_salesforcelead_organizationId'][0];
    if (!$organizationId) {
        $organizationId = snp_get_option('ml_salesforcelead_organizationId');
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
        $response = \Httpful\Request::post('https://webto.salesforce.com/servlet/servlet.WebToLead?encoding=UTF-8')
            ->body($data)
            ->send();

        $result['status'] = true;
    } catch (Exception $e) {
        $result['log']['errorMessage'] = 'Salesfore Web-To-Lead error message: ' . $e->getMessage();
    }

    return $result;
}