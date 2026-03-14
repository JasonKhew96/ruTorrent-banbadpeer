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
        $req = new rXMLRPCRequest(array(
            new rXMLRPCCommand("p.multicall", array($magnetHash, "", getCmd("p.id="), getCmd("p.address="), getCmd("p.id_html="), getCmd("p.completed_percent="), getCmd("p.up_total="), getCmd("p.banned="), getCmd("p.is_snubbed="))),
        ));
        if (!$req->success()) {
            // error
            exit(0);
        }
        for ($i = 0; $i < count($req->val); $i+=5) {
            $peerHashID = $req->val[$i];
            $peerIP = $req->val[$i + 1];
            $peerID = $req->val[$i + 2];
            $completedPercent = $req->val[$i + 3];
            $upTotal = $req->val[$i + 4];
            $isBanned = $req->val[$i + 5];
            $isSnubbed = $req->val[$i + 6];
            if ($shadowBan && $isSnubbed) {
                continue;
            }
            if (preg_match($badPeerRegex, $peerID) || ($completedPercent == 0 && $upTotal > 52428800)) {
                $reqBanPeer = NULL;
                if ($shadowBan) {
                    $reqBanPeer = new rXMLRPCRequest(array(
                        new rXMLRPCCommand("p.snubbed.set", array($magnetHash . ":p" . $peerHashID, 1)),
                    ));
                } else {
                    $reqBanPeer = new rXMLRPCRequest(array(
                        new rXMLRPCCommand("p.banned.set", array($magnetHash . ":p" . $peerHashID, 1)),
                        new rXMLRPCCommand("p.disconnect", $magnetHash . ":p" . $peerHashID),
                    ));
                }
                if (!$reqBanPeer->success()) {
                    // error
                    exit(0);
                }
                if ($logToFile) {
                    FileUtil::toLog("banbadpeer: banned " . $magnetHash . " " . $peerIP . " " . $peerID . " " . $completedPercent . " " . $upTotal);
                }
            }
        }
    }
}

// success
exit(0);
