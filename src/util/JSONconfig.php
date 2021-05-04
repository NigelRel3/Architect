<?php
declare(strict_types=1);

namespace Architect\util;

error_reporting(E_ALL);
ini_set('display_errors', "1");

require_once __DIR__ . '/../../vendor/autoload.php';

use Architect\data\architect\AvailablePanelTypes;
use Architect\data\architect\Menu;
use Architect\data\architect\StatsType;
use Architect\data\architect\StatsTypeParent;
use Architect\data\architect\Workspace;
use Dotenv\Dotenv;
use PDO;

/**
 * update Menu set config = JSON_REMOVE(config, '$.dataSourcesLoaded') where `key` = 9 and WorkspaceID = 1
 *
 */
/**
Reset windows field
UPDATE `Workspace` SET `Windows` = '[\r\n    {\r\n        \"type\": \"window\",\r\n        \"children\": [\r\n            {\r\n                \"size\": 20,\r\n                \"type\": \"pane\",\r\n                \"children\": [\r\n                    {\r\n                        \"key\": 0,\r\n                        \"type\": \"tab\"\r\n                    }\r\n                ],\r\n                \"openToThisPanel\": false\r\n            },\r\n            {\r\n                \"size\": 38.11,\r\n                \"type\": \"pane\",\r\n                \"children\": [\r\n                    {\r\n                        \"key\": 9,\r\n                        \"type\": \"tab\"\r\n                    },\r\n                    {\r\n                        \"key\": 3,\r\n                        \"type\": \"tab\"\r\n                    },\r\n                    {\r\n                        \"key\": 6,\r\n                        \"type\": \"tab\"\r\n                    }\r\n                ],\r\n                \"autoSized\": false,\r\n                \"openToThisPanel\": false\r\n            },\r\n            {\r\n                \"size\": 40.89,\r\n                \"type\": \"pane\",\r\n                \"children\": [\r\n                    {\r\n                        \"key\": 1,\r\n                        \"type\": \"tab\"\r\n                    }\r\n                ],\r\n                \"autoSized\": true,\r\n                \"openToThisPanel\": false\r\n            }\r\n        ],\r\n        \"openToThisPanel\": false\r\n    }\r\n]' WHERE `Workspace`.`id` = 1;
*/

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

$dbName = $_ENV["DB_DBNAME"];
$db = new \PDO("mysql:host=".$_ENV["DB_HOST"].";dbname=".$dbName,
		$_ENV["DB_USER"], $_ENV["DB_PASSWD"]);
$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$st = new StatsType($db);

$st->id = 1;
$st->Name = 'jmeterimport';
$st->Description = 'Fields common in JMeter performance files';
$st->insert();

$st->id = 2;
$st->Name = 'timeStamp';
$st->Description = 'timeStamp';
$st->insert();
$st->id = 3;
$st->Name = 'elapsed';
$st->Description = 'elapsed';
$st->insert();
$st->id = 4;
$st->Name = 'label';
$st->Description = 'label';
$st->insert();
$st->id = 5;
$st->Name = 'responseCode';
$st->Description = 'responseCode';
$st->insert();
$st->id = 6;
$st->Name = 'responseMessage';
$st->Description = 'responseMessage';
$st->insert();
$st->id = 7;
$st->Name = 'threadName';
$st->Description = 'threadName';
$st->insert();
$st->id = 8;
$st->Name = 'dataType';
$st->Description = 'dataType';
$st->insert();
$st->id = 9;
$st->Name = 'success';
$st->Description = 'success';
$st->insert();
$st->id = 10;
$st->Name = 'bytes';
$st->Description = 'bytes';
$st->insert();
$st->id = 11;
$st->Name = 'grpThreads';
$st->Description = 'grpThreads';
$st->insert();
$st->id = 12;
$st->Name = 'allThreads';
$st->Description = 'allThreads';
$st->insert();
$st->id = 13;
$st->Name = 'URL';
$st->Description = 'URL';
$st->insert();
$st->id = 14;
$st->Name = 'Latency';
$st->Description = 'Latency';
$st->insert();
$st->id = 15;
$st->Name = 'Connect';
$st->Description = 'Connect';
$st->insert();
$st->id = 16;
$st->Name = 'PerformanceID';
$st->Description = 'PerformanceID';
$st->insert();

$stp = new StatsTypeParent($db);
for( $i = 2; $i <= 16; $i++ )	{
	$stp->StatsTypeID = $i;
	$stp->ParentTypeID = 1;
	$stp->insert();
}

$st->id = 21;
$st->Name = 'dockerimport';
$st->Description = 'Fields common in Docker';
$st->insert();

$st->id = 22;
$st->Name = 'lapsed';
$st->Description = 'lapsed';
$st->insert();
$st->id = 23;
$st->Name = 'cputotal';
$st->Description = 'cputotal';
$st->insert();
$st->id = 24;
$st->Name = 'cpukernel';
$st->Description = 'cpukernel';
$st->insert();
$st->id = 25;
$st->Name = 'cpuuser';
$st->Description = 'cpuuser';
$st->insert();
$st->id = 26;
$st->Name = 'memoryusage';
$st->Description = 'memoryusage';
$st->insert();
$st->id = 27;
$st->Name = 'memorymax';
$st->Description = 'memorymax';
$st->insert();
$st->id = 28;
$st->Name = 'blkioRead';
$st->Description = 'blkioRead';
$st->insert();
$st->id = 29;
$st->Name = 'blkioWrite';
$st->Description = 'blkioWrite';
$st->insert();
$st->id = 30;
$st->Name = 'blkioTotal';
$st->Description = 'blkioTotal';
$st->insert();
$st->id = 31;
$st->Name = 'networkRx_bytes';
$st->Description = 'networkRx_bytes';
$st->insert();
$st->id = 32;
$st->Name = 'networkTx_bytes';
$st->Description = 'networkTx_bytes';
$st->insert();


$stp = new StatsTypeParent($db);
for( $i = 22; $i <= 32; $i++ )	{
	$stp->StatsTypeID = $i;
	$stp->ParentTypeID = 21;
	$stp->insert();
}

// SAR
$st->id = 40;
$st->Name = 'SARimport';
$st->Description = 'Fields in SAR import';
$st->insert();
# hostname;interval;timestamp;CPU;%user;%nice;%system;%iowait;%steal;%idle
$st->id = 41;
$st->Name = 'CPU';
$st->Description = 'CPU';
$st->insert();
$st->id = 42;
$st->Name = 'CPU%user';
$st->Description = 'CPU%user';
$st->insert();
$st->id = 43;
$st->Name = 'CPU%system';
$st->Description = 'CPU%system';
$st->insert();
$st->id = 44;
$st->Name = 'CPU%idle';
$st->Description = 'CPU%idle';
$st->insert();
$st->id = 55;
$st->Name = 'CPU%iowait';
$st->Description = 'CPU%iowait';
$st->insert();
# hostname;interval;timestamp;kbmemfree;kbavail;kbmemused;%memused;kbbuffers;kbcached;kbcommit;%commit;kbactive;kbinact;kbdirty
$st->id = 45;
$st->Name = 'memory';
$st->Description = 'memory';
$st->insert();
$st->id = 46;
$st->Name = 'kbmemfree';
$st->Description = 'kbmemfree';
$st->insert();
$st->id = 47;
$st->Name = 'kbavail';
$st->Description = 'kbavail';
$st->insert();
$st->id = 48;
$st->Name = 'memory%memused';
$st->Description = '%memused';
$st->insert();
# hostname;interval;timestamp;tps;rtps;wtps;dtps;bread/s;bwrtn/s;bdscd/s
$st->id = 49;
$st->Name = 'blkio';
$st->Description = 'blkio';
$st->insert();
$st->id = 50;
$st->Name = 'bread/s';
$st->Description = 'bread/s';
$st->insert();
$st->id = 51;
$st->Name = 'bwrtn/s';
$st->Description = 'bwrtn/s';
$st->insert();
# hostname;interval;timestamp;IFACE;rxpck/s;txpck/s;rxkB/s;txkB/s;rxcmp/s;txcmp/s;rxmcst/s;%ifutil
$st->id = 52;
$st->Name = 'network';
$st->Description = 'network';
$st->insert();
$st->id = 53;
$st->Name = 'rxkB/s';
$st->Description = 'rxkB/s';
$st->insert();
$st->id = 54;
$st->Name = 'txkB/s';
$st->Description = 'txkB/s';
$st->insert();


$stp = new StatsTypeParent($db);
$stp->StatsTypeID = 41;
$stp->ParentTypeID = 40;
$stp->insert();
$stp->StatsTypeID = 42;
$stp->ParentTypeID = 41;
$stp->insert();
$stp->StatsTypeID = 43;
$stp->ParentTypeID = 41;
$stp->insert();
$stp->StatsTypeID = 44;
$stp->ParentTypeID = 41;
$stp->insert();
$stp->StatsTypeID = 45;
$stp->ParentTypeID = 40;
$stp->insert();
$stp->StatsTypeID = 46;
$stp->ParentTypeID = 45;
$stp->insert();
$stp->StatsTypeID = 47;
$stp->ParentTypeID = 45;
$stp->insert();
$stp->StatsTypeID = 48;
$stp->ParentTypeID = 45;
$stp->insert();
$stp->StatsTypeID = 49;
$stp->ParentTypeID = 40;
$stp->insert();
$stp->StatsTypeID = 50;
$stp->ParentTypeID = 49;
$stp->insert();
$stp->StatsTypeID = 51;
$stp->ParentTypeID = 49;
$stp->insert();
$stp->StatsTypeID = 52;
$stp->ParentTypeID = 40;
$stp->insert();
$stp->StatsTypeID = 53;
$stp->ParentTypeID = 52;
$stp->insert();
$stp->StatsTypeID = 54;
$stp->ParentTypeID = 52;
$stp->insert();

exit;
/**
 * Workspace..........................................
 */
$ws = new Workspace($db);
$ws->fetch([1]);

$config = [ "name" => "default",
	"horizontal" => true,
	"sizeable" => true,
	'panes' => [[
		"size" => 20,
		"tabs" => [0]
	],
		[
			"tabs" => [1], "openToThisPanel" => true
		]
	]
];
$ws->Config = $config;
$ws->update();

$m1 = new Menu($db);

$m1->set(["id" => 1, "key" => 1, "title" => 'Default',
		"folder" => 1, "editable" => 1, "addContents" => 1,
		"addFolder" => 1, "nextKey" => 10,
		"deleteable" => 0, "componentID" => 2,
		"icon" => '/ui/icons/archive.svg',
		"addIcon" => 'book',
		"config" => [
				"componentID" => 2,
				'description' => 'Default wirkspace'
		],
		"WorkspaceID" => 1, "parentMenuID" => null,
		"addChildData" => [
			"componentID" => 8,
			"editable" => true,
			"deleteable" => true,
			"addFolder" => true,
			"addContents" => true,
			"componentID" => 8,
		]
]);
$m1->insert();

$m1->set(["id" => 7, "key" => 9, "title" => 'a',
    "folder" => 1, "editable" => 1, "addContents" => 0,
    "addFolder" => 0, "addIcon" => null, "nextKey" => 0,
    "deleteable" => 1, "componentID" => 4,
    "icon" => '/ui/icons/receipt-cutoff.svg',
    "config" => [
        "editable" => true,
        "deleteable" => true,
        "addFolder" => true,
        "addContents" => true,
        "componentID" => 8
    ],
    "WorkspaceID" => 1, "parentMenuID" => 1
]);
$m1->insert();

$m1->set(["id" => 2, "key" => 2, "title" => 'Data Loads',
		"folder" => 1, "editable" => 0, "addContents" => 1,
		"addFolder" => 1, "addIcon" => 'receipt-cutoff', "nextKey" => 0,
		"deleteable" => 0, "componentID" => null,
		"icon" => null,
		"config" => [
		],
		"WorkspaceID" => 1, "parentMenuID" => null,
	"addChildData" => [
		"editable" => true,
		"deleteable" => true,
		"ComponentID" => 4,
	]
]);
$m1->insert();

$m1->set(["id" => 3, "key" => 3, "title" => 'JMeter 9/Dec/2020 19:45',
		"folder" => 0, "editable" => 1, "addContents" => 0,
		"addFolder" => 0, "addIcon" => null, "nextKey" => 0,
		"deleteable" => 1, "componentID" => 4,
		"icon" => '/ui/icons/receipt-cutoff.svg',
		"config" =>[ "loadID"=> 32, "importType" => 5],
		"WorkspaceID" => 1, "parentMenuID" => 2
]);
$m1->insert();

$m1->set(["id" => 4, "key" => 4, "title" => 'JMeter 9/Dec/2020 20:45',
		"folder" => 0, "editable" => 1, "addContents" => 0,
		"addFolder" => 0, "addIcon" => null, "nextKey" => 0,
		"deleteable" => 1, "componentID" => 4,
		"icon" => '/ui/icons/receipt-cutoff.svg',
		"config" => [ "loadID"=> 33, "importType" => 6],
		"WorkspaceID" => 1, "parentMenuID" => 2
]);
$m1->insert();
$m1->set(["id" => 6, "key" => 6, "title" => 'JMeter 9/Dec/2021 20:47',
		"folder" => 0, "editable" => 1, "addContents" => 0,
		"addFolder" => 0, "addIcon" => null, "nextKey" => 0,
		"deleteable" => 1, "componentID" => 4,
		"icon" => '/ui/icons/receipt-cutoff.svg',
		"config" => [ "importType" => 6],
		"WorkspaceID" => 1, "parentMenuID" => 2
]);
$m1->insert();
$m1->set(["id" => 5, "key" => 5, "title" => 'Tools',
		"folder" => 1, "editable" => 0, "addContents" => 0,
		"addFolder" => 0, "addIcon" => null, "nextKey" => 0,
		"deleteable" => 1, "componentID" => 4,
		"icon" => '/ui/icons/gear-fill.svg',
		"config" => null,
		"WorkspaceID" => 1, "parentMenuID" => null
]);
$m1->insert();

$m1->set(["id" => 7, "key" => 9, "title" => 'a',
		"folder" => 0, "editable" => 1, "addContents" => 0,
		"addFolder" => 0, "addIcon" => null, "nextKey" => 0,
		"deleteable" => 1, "componentID" => 8,
		"icon" => '/ui/icons/receipt-cutoff.svg',
		"config" => [
		],
		"WorkspaceID" => 1, "parentMenuID" => 1
]);
$m1->insert();
// /*
//  * For panes within panes,
// $config2 = [ "name" => "d2", "horizontal" => false, "sizeable" => true ];
// // etc...
// $config['pane2'] = [ "size" => 80, "content" => $config2 ];

//  */

/**
 * AvailablePanelTypes - pane1..........................................
 */
$apt = new AvailablePanelTypes($db);
// $apt->fetch([1]);
$apt->id = 1;
$apt->Name = "mainmenu";
$apt->Config = [ "name" => "pane1a",
		"additionalSource" => "<link href=\"https://cdn.jsdelivr.net/npm/jquery.fancytree@2.27/dist/skin-win8/ui.fancytree.min.css\" rel=\"stylesheet\">
        <script src=\"https://cdn.jsdelivr.net/npm/jquery.fancytree@2.27/dist/jquery.fancytree-all-deps.min.js\"></script>
"
];
$apt->ComponentName = "mainmenu";
$apt->insert();

/**
 * AvailablePanelTypes - pane2..........................................
 */
$apt = new AvailablePanelTypes($db);
//$apt->fetch([2]);
$apt->id = 2;
$apt->Name = "projectdetails";
$apt->Config = [ "name" => "pane2a" ];
$apt->ComponentName = "projectdetails";
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 3;
$apt->Name = "TestTool";
$apt->Config = [
		"menu" => [
			"title" => "TestTool",
			"icon" => "/ui/icons/pencil.svg"
		]
];
$apt->ComponentName = "testtool";
$apt->ParentMenuKey = 5;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 4;
$apt->Name = "DataImport";
$apt->Config = [
		"menu" => [
				"title" => "DataImport",
				"icon" => "/ui/icons/pencil.svg"
		]
];
$apt->ComponentName = "dataimport";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 5;
$apt->Name = "jmeterimport";
$apt->Config = [
		"menu" => [
				"title" => "jmeterimport",
				"icon" => "/ui/icons/pencil.svg"
		]
];
$apt->ParentPanelTypeID = 4;
$apt->ComponentName = "jmeterimport";
$apt->ParentPanelContext="ImportType";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 6;
$apt->Name = "sarimport";
$apt->Config = [
		"menu" => [
				"title" => "sarimport",
				"icon" => "/ui/icons/pencil.svg"
		]
];
$apt->ParentPanelTypeID = 4;
$apt->ComponentName = "sarimport";
$apt->ParentPanelContext="ImportType";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 7;
$apt->Name = "filechooser";
$apt->Config = [
		"menu" => [
				"title" => "filechooser",
				"icon" => "/ui/icons/pencil.svg"
		]
];
$apt->ParentPanelTypeID = 4;
$apt->ComponentName = "filechooser";
$apt->ParentPanelContext="FileChooser";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 8;
$apt->Name = "report";
$apt->Config = [
		"menu" => [
				"title" => "report",
				"icon" => "/ui/icons/book.svg"
		]
];
$apt->ComponentName = "report";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 9;
$apt->Name = "simpledaterange";
$apt->Config = [
		"menu" => [
				"title" => "simpledaterange",
				"icon" => "/ui/icons/book.svg"
		]
];
$apt->ParentPanelTypeID = 8;
$apt->ComponentName = "simpledaterange";
$apt->ParentPanelContext="DateRange";
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 10;
$apt->Name = "sourceselector";
$apt->Config = [
		"menu" => [
				"title" => "sourceselector",
				"icon" => "/ui/icons/book.svg"
		]
];
$apt->ParentPanelTypeID = 8;
$apt->ComponentName = "sourceselector";
$apt->ParentPanelContext="SourceSelector";
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 11;
$apt->Name = "simplegraph";
$apt->Config = [
	"menu"=> [
		"icon"=> "/ui/icons/book.svg",
		"title"=> "simplegraph"
	],
	"additionalSource"=> "<script src=\"https://cdn.jsdelivr.net/npm/chart.js@2.8.0\"></script>"
];
$apt->ParentPanelTypeID = 8;
$apt->ComponentName = "simplegraph";
$apt->ParentPanelContext="Graph";
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 12;
$apt->Name = "Docker Import";
$apt->Config = [
	"menu" => [
		"title" => "Docker Import",
		"icon" => "/ui/icons/pencil.svg"
	]
];
$apt->ParentPanelTypeID = 4;
$apt->ComponentName = "dockerimport";
$apt->ParentPanelContext="ImportType";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 13;
$apt->Name = "Date Range Slider";
$apt->Config = [
	"menu" => [
		"title" => "Date Range Slider",
		"icon" => "/ui/icons/pencil.svg"
	]
];
$apt->ParentPanelTypeID = 4;
$apt->ComponentName = "daterangeslider";
$apt->ParentPanelContext="DateRange";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 14;
$apt->Name = "Source Data Types";
$apt->Config = [
	"menu" => [
		"title" => "Source Data Types",
		"icon" => "/ui/icons/pencil.svg"
	]
];
$apt->ParentPanelTypeID = 8;
$apt->ComponentName = "sourcetypeselector";
$apt->ParentPanelContext="SourceSelector";
$apt->ParentMenuKey = 0;
$apt->insert();

$apt = new AvailablePanelTypes($db);
$apt->id = 15;
$apt->Name = "scattergraph";
$apt->Config = [
	"menu"=> [
		"icon"=> "/ui/icons/book.svg",
		"title"=> "Scatter Graph"
	],
	"additionalSource"=> "<script src=\"https://cdn.jsdelivr.net/npm/chart.js@2.8.0\"></script>"
];
$apt->ParentPanelTypeID = 8;
$apt->ComponentName = "scattergraph";
$apt->ParentPanelContext="Graph";
$apt->insert();
