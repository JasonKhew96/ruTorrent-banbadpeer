<?php
if (count($argv) > 1)
    $_SERVER['REMOTE_USER'] = $argv[1];
$path = dirname(realpath($argv[0]));
if (chdir($path)) {
    require_once(dirname(__FILE__) . '/../../php/xmlrpc.php');
    require_once(dirname(__FILE__) . "/../../php/util.php");
    eval(FileUtil::getPluginConf('banbadpeer'));
    $reqDownloadList = new rXMLRPCRequest(array(
        new rXMLRPCCommand("download_list"),
    ));
    $reqDownloadList->setParseByTypes();
    if (!$reqDownloadList->success()) {
        // error
        exit(0);
    }
    foreach ($reqDownloadList->strings as $magnetHash) {
        $reqPeerList = new rXMLRPCRequest(array(
            new rXMLRPCCommand("p.multicall", array($magnetHash, "", getCmd("p.id="), getCmd("p.address="), getCmd("p.id_html="), getCmd("p.banned") . "=0")),
        ));
        $reqPeerList->setParseByTypes();
        if (!$reqPeerList->success()) {
            // error
            exit(0);
        }
        for ($i = 0; $i < count($reqPeerList->strings) / 3; $i++) {
            $peerHashID = $reqPeerList->strings[$i * 3];
            $peerIP = $reqPeerList->strings[$i * 3 + 1];
            $peerID = $reqPeerList->strings[$i * 3 + 2];
            if (preg_match($badPeerRegex, $peerID)) {
                $reqBanPeer = new rXMLRPCRequest(array(
                    new rXMLRPCCommand("p.banned.set", array($magnetHash . ":p" . $peerHashID, 1)),
                    new rXMLRPCCommand("p.disconnect", $magnetHash . ":p" . $peerHashID),
                ));
                if (!$reqBanPeer->success()) {
                    // error
                    exit(0);
                }
                if ($logToFile) {
                    $dt = (new DateTime())->format(DateTime::ATOM);
                    $logStream = fopen("log.txt", "a");
                    fwrite($logStream, $dt . " " . $peerIP . " " . $peerID . PHP_EOL);
                    fclose($logStream);
                }
            }
        }
    }
}

// success
exit(0);
