<?php
require_once __DIR__ . '/inc/common.php';
require_once __DIR__ . '/inc/alsa.php';
require_once __DIR__ . '/inc/sql.php';

class AppleMeta {
    public $track;
    public $artist;
    public $cover;

    public function __construct() {
        $parts = explode('~~~', file_get_contents(APLMETA_FILE));
        if (count($parts) === 6) {
          $this->track  = $parts[0];
          $this->artist = $parts[1];
          $this->cover  = str_replace('\/', '/', $parts[4]);
        } else {
          $this->track  = "INVALID_FORMAT";
        }
    }
}

function parseDeviceName($deviceList) {
  $parts = explode(' ', $deviceList[2], 3);
  if (isset($parts[2])) {
    return $parts[2]; 
  }
}

$dbh = sqlConnect();

$data = [];
$result = sysCmd('pgrep -l bluealsa-aplay');
$btActive = strpos($result[0], 'bluealsa-aplay') !== false;
$aplActive = sqlQuery("SELECT value FROM cfg_system WHERE param='aplactive'", $dbh)[0]['value'];
$audioout = sqlQuery("SELECT value FROM cfg_system WHERE param='audioout'", $dbh)[0]['value'];
$source = "undefined";
if ($aplActive == '1') {
  $data['source'] = "AirPlay";
  $data['metadata'] = get_object_vars(new AppleMeta());
} else if ($btActive === true && $audioout == 'Local') {
  $data['source'] = "Bluetooth";
  $data['device'] = parseDeviceName(sysCmd('/var/www/util/blu-control.sh -c'));
} else {
  $data['source'] = "Local";
}


header('Content-Type: application/json');
echo json_encode($data, JSON_PRETTY_PRINT);
?>
