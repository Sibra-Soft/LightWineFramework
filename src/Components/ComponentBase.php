<?php
namespace LightWine\Components;

use LightWine\Modules\Database\Services\MysqlConnectionService;
use LightWine\Core\Helpers\Helpers;
use LightWine\Core\Interfaces\IComponentBase;
use LightWine\Core\Helpers\ConvertHelpers;

class ComponentBase implements IComponentBase
{
    private MysqlConnectionService $databaseConnection;

    public function __construct(){
        $this->databaseConnection = new MysqlConnectionService();
    }

    /**
     * Gets the settings of the specified component
     * @param object $controlInstance The instance of the component
     * @param int $componentId The id of the component
     * @return object A object containing all the settings of the component
     */
    public function GetSettings($controlInstance, int $componentId): object {
        $array = [];

        $this->databaseConnection->ClearParameters();
        $this->databaseConnection->AddParameter("controlId", $componentId);
        $this->databaseConnection->GetDataset("
            SELECT
	            version.content AS settings
            FROM `site_templates` AS component
            INNER JOIN site_template_versioning AS version ON version.version = component.template_version_dev AND version.template_id = component.id
            WHERE component.`id` = ?controlId
	            AND component.type = 'component'
            LIMIT 1;
        ");

        if($this->databaseConnection->rowCount > 0){
            $array = json_decode(Helpers::RepairJson($this->databaseConnection->DatasetFirstRow("settings")), true);
        }

        return ConvertHelpers::ArrayToObject($array, $controlInstance);
    }
}