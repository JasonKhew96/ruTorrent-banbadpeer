<?php

eval(FileUtil::getPluginConf($plugin["name"]));
$req = new rXMLRPCRequest($theSettings->getAbsScheduleCommand(
    "banbadpeer",
    $updateInterval,
    getCmd('execute') . '={sh,-c,' . escapeshellarg(Utility::getPHP()) . ' ' . escapeshellarg($rootPath . '/plugins/banbadpeer/update.php') . ' ' . escapeshellarg(User::getUser()) . ' & exit 0}'
));
if (!$req->success()) {
    $jResult .= "plugin.disable(); noty('banbadpeer: '+theUILang.pluginCantStart,'error');";
    exit(0);
}
