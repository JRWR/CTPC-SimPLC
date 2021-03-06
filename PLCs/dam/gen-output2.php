#!/usr/bin/php
<?php
$plcname = "DAM-GENOUT2";
require "template.php";
/// DAM POWER GENERATORS POWER OUTPUT #2
/// READINGS RETURN MEGAWATTS
/// 
/// PORT 0000 - MW OUTPUT OF GEN2
///           - WRITE: SET TO 1 TO DISABLE BYPASS, SET TO >1 TO ENGAGE BYPASS, BYPASS SETS GEN TO FREESPIN MODE!


//////////////////////////////////////////
/// MEMCACHE KEYS
/////////////////////////////////////////
/// DAM-GENOUT2 = MW OUTPUT OF GEN2
/// DAM-GENOUT2-BYPASS = SET TO 1 TO DISABLE BYPASS, SET TO >1 TO ENGAGE BYPASS
////////////////////////////////////////

logme("===== STARTUP =====");
function process_logic($input)
{
    ///$input
    /// {"port":"0000","action":"1","value":98,"mfg":15}
    global $m;
    $res = read_data($input);
    if ($res !== false) {
        logme("response OK, " . json_encode($res));
        if ($res["act"] === "1") {
            //read a sensor
                        
            switch ($res["port"]) {
                case "0000":

                        $flowrate = $m->get('DAM-GENOUT2');
                        if (empty($flowrate)) {
                            //NO POWERRRR
                            $m->set('DAM-GENOUT2', 0.00);
                            $flowrate = 0.00;
                            logme('Memcache DAM-GENOUT2 Empty, retin to 0.00MW');
                        }
                        logme('Sending DAM-GENOUT2 of ' . $flowrate);
                        //crash on invaild data
                        if ($res["value"] !== "0000") {
                            simcrash("Invaild Value for Reading DAM-GENOUT2, CRASHING!");
                            break;
                        }
                        ///write_data($type, $port, $number, $mfg1)
                        $data = write_data("1", "0000", $flowrate, 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;

                    default:
                        logme('Sending Unused Port ' . $res["port"]. " false data");
                        $data = write_data("1", $res["port"], floor(rand(5, 100)), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
    }

            return true;
        }
        if ($res["act"] === "0") {
            switch ($res["port"]) {
                    case "0000":
                        logme("DAM-GENOUT2-BYPASS to ".$res["value"]);
                        if ($res["value"] === "0000") {
                            simcrash("Invaild Value for DAM-GENOUT2-BYPASS");
                            break;
                        }
                        if (bindec($res["value"]) > 10) {
                            simcrash("Invaild Value for DAM-GENOUT2-BYPASS");
                            break;
                        }
                        logme("Setting DAM-GENOUT2-BYPASS to " . bindec($res["value"]) . "0%");
                        $m->set('DAM-GENOUT2-BYPASS', bindec($res["value"]));
                        $data = write_data("0", $res["port"], bindec($res["value"]), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
                    default:
                        logme('Sending Unused Port ' . $res["port"]. " false data");
                        $data = write_data("0", $res["port"], floor(rand(5, 100)), 0);
                        fwrite(STDOUT, pack('H*', base_convert("00" . $data, 2, 16)));
                    break;
            }
            return true;
        }
        simcrash("Act Failure? Bailing!");
        return false;
    } else {
        logme("read_data failed, bailing");
        return false;
    }
}

$input = base_convert(bin2hex(fread(STDIN, 6)), 16, 2);
process_logic($input);
logme("==== DONE ====");
