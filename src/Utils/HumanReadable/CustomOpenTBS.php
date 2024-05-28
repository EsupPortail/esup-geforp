<?php
/**
 * Created by PhpStorm.
 * User: erwan
 * Date: 10/5/17
 * Time: 11:31 AM.
 */

namespace App\Utils\HumanReadable;

require_once __DIR__.'/../../../vendor/tinybutstrong/tinybutstrong/tbs_class.php';
require_once __DIR__.'/../../../vendor/tinybutstrong/opentbs/tbs_plugin_opentbs.php';

/**
 * Service for OpenTBS Bundle.
 */
class CustomOpenTBS extends CustomTinyButStrong
{
    public function __construct()
    {
        // construct the TBS class
        parent::__construct();

        // load the OpenTBS plugin
        $this->Plugin(TBS_INSTALL, OPENTBS_PLUGIN);
    }
}
